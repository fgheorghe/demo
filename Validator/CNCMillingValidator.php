<?php declare(strict_types = 1);

require "vendor/guiguiboy/php-cli-progress-bar/ProgressBar/Manager.php";
require "vendor/guiguiboy/php-cli-progress-bar/ProgressBar/Registry.php";
use \ProgressBar\Manager;

/**
 * Class CNCMillingValidator
 */
class CNCMillingValidator
{
    /** @var CNCMachine $cncMachine */
    private $cncMachine;

    /** @var string $gCode */
    private $gCode;

    /** @var float $maxZ */
    private $maxZ = 0;

    /** @var float $maxX */
    private $maxX = 0;

    /** @var float $maxY */
    private $maxY = 0;

    /** @var array $polygons */
    private $polygons = array();

    /**
     * @return array
     */
    public function getPolygons() : array
    {
        return $this->polygons;
    }

    /**
     * @param array $polygons
     * @return CNCMillingValidator
     */
    public function setPolygons(array $polygons) : CNCMillingValidator
    {
        $this->polygons = $polygons;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxZ() : float
    {
        return $this->maxZ;
    }

    /**
     * @param float $maxZ
     * @return CNCMillingValidator
     */
    public function setMaxZ(float $maxZ) : CNCMillingValidator
    {
        $this->maxZ = $maxZ;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxX() : float
    {
        return $this->maxX;
    }

    /**
     * @param float $maxX
     * @return CNCMillingValidator
     */
    public function setMaxX(float $maxX) : CNCMillingValidator
    {
        $this->maxX = $maxX;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxY() : float
    {
        return $this->maxY;
    }

    /**
     * @param float $maxY
     * @return CNCMillingValidator
     */
    public function setMaxY(float $maxY) : CNCMillingValidator
    {
        $this->maxY = $maxY;
        return $this;
    }

    /**
     * @return CNCMachine
     */
    public function getCncMachine() : CNCMachine
    {
        return $this->cncMachine;
    }

    /**
     * @param CNCMachine $cncMachine
     * @return CNCMillingValidator
     */
    public function setCncMachine(CNCMachine $cncMachine) : CNCMillingValidator
    {
        $this->cncMachine = $cncMachine;
        return $this;
    }

    /**
     * @return string
     */
    public function getGCode() : string
    {
        return $this->gCode;
    }

    /**
     * @param string $gCode
     * @return CNCMillingValidator
     */
    public function setGCode(string $gCode) : CNCMillingValidator
    {
        $this->gCode = $gCode;
        return $this;
    }

    /**
     * CNCMillingValidator constructor.
     * @param CNCMachine $CNCMachine
     * @param string $gCode
     */
    public function __construct(CNCMachine $CNCMachine, string $gCode)
    {
        $this->setCncMachine($CNCMachine)
            ->setGCode($gCode)
        ;
    }

    public function validate()
    {
        $this->unpack();
        $this->canFit();
    }

    private function canFit()
    {
        if ($this->getMaxX() > $this->getCncMachine()->getWidth()) throw new DoesNotFitException("Too wide.");
        if ($this->getMaxY() > $this->getCncMachine()->getLength()) throw new DoesNotFitException("Too long.");
        if ($this->getMaxZ() > $this->getCncMachine()->getHeight()) throw new DoesNotFitException("Too thick.");
        if (!$this->canReach()) throw new UnreachableAreaException();
    }

    // Builds segments of a layer, used for verifying intersections.
    private function buildLayerLines(array $coordinates)
    {
        $lines = array();

        for ($i = 1; $i < count($coordinates); $i++) {
            $lines[] =
                $coordinates[$i - 1][0] . " " . $coordinates[$i - 1][1];
            $lines[] =
                $coordinates[$i][0] . " " . $coordinates[$i][1];
        }

        $lines[] =
            $coordinates[$i - 1][0] . " " . $coordinates[$i - 1][1];
        $lines[] =
            $coordinates[0][0] . " " . $coordinates[0][1];

        return $lines;
    }

    /**
     * Verifies if all polygons at a lower level fit in polygons at the top level.
     */
    private function canReach() : bool
    {
        $layers = $this->getPolygons();
        $progressBar = new Manager(0, count($layers) - 1);

        $topLayerLines = $this->buildLayerLines($layers[0]);
        $pointLocation = new pointLocation();

        for ($i = 1; $i < count($layers); $i++) {
            if (count($layers[$i]) != 0) {
                foreach ($layers[$i] as $point) {
                    if ($pointLocation->pointInPolygon(
                        $point[0] . " " . $point[1],
                        $topLayerLines,
                        true
                    ) == "outside") {
                        $progressBar->update($i - 1);
                        return false;
                    }
                }
            }
            $progressBar->update($i - 1);
        }

        return true;
    }

    /**
     * Extracts various info about the instruction set.
     */
    private function unpack()
    {
        $maxX = 0;
        $maxY = 0;
        $maxZ = 0;

        $lines = explode("\n", $this->getGCode());

        $polygons = array();
        $polygon = -1;

        foreach ($lines as $line) {
            $command = explode(" ", $line);

            switch ($command[0]) {
                case "G1":
                    if (count($command) == 2) {
                        $z = (float) substr($command[1], 1);
                        if ($z > $maxZ) $maxZ = $z;
                        $polygon++;
                        $polygons[$polygon] = array();
                    } elseif (count($command) == 4) {
                        $x = (float) substr($command[1], 1);
                        $y = (float) substr($command[2], 1);
                        $z = (float) substr($command[3], 1);
                        if ($x > $maxX) $maxX = $x;
                        if ($y > $maxY) $maxY = $y;
                        if ($z > $maxZ) $maxZ = $z;
                        $polygons[$polygon][] = array($x, $y, $z);
                    }
                    break;
            }
        }

        $this->setMaxZ($maxZ)
            ->setMaxX($maxX)
            ->setMaxY($maxY)
            ->setPolygons($polygons);
    }
}