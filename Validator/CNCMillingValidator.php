<?php declare(strict_types = 1);

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

    // Based on: http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
    private function doLinesIntersect(float $p0x, float $p0y, float $p1x, float $p1y, float $p2x, float $p2y, float $p3x, float $p3y ) : bool
    {
        $s1x = $p1x - $p0x; $s1y = $p1y - $p0y;
        $s2x = $p3x - $p2x; $s2y = $p3y - $p2y;

        if ((-1 * $s2x * $s1y + $s1x * $s2y) != 0) {
            $s = (-1 * $s1y * ($p0x - $p2x) + $s1x * ($p0y - $p2y)) / (-1 * $s2x * $s1y + $s1x * $s2y);
        } else {
            $s = 0;
        }
        if ((-1 * $s2x * $s1y + $s1x * $s2y) != 0) {
            $t = ($s2x * ($p0y - $p2y) - $s2y * ($p0x - $p2x)) / (-1 * $s2x * $s1y + $s1x * $s2y);
        } else {
            $t = 0;
        }

        return ($s >= 0 && $s <= 1 && $t >= 0 && $t <= 1);
    }

    // Builds segments of a layer, used for verifying intersections.
    private function buildLayerLines(array $coordinates)
    {
        $lines = array();

        for ($i = 1; $i < count($coordinates); $i++) {
            $lines[] = array(
                $coordinates[$i - 1],
                $coordinates[$i]
            );
        }

        $lines[] = array(
            $coordinates[$i - 1],
            $coordinates[0]
        );

        return $lines;
    }

    /**
     * Verifies if all polygons at a lower level fit in polygons at the top level.
     */
    private function canReach() : bool
    {
        $layers = $this->getPolygons();
        $topLayerLines = $this->buildLayerLines($layers[0]);

        for ($i = 1; $i < count($layers); $i++) {
            if (count($layers[$i]) != 0) {
                $layerLines = $this->buildLayerLines($layers[$i]);
                foreach ($topLayerLines as $topLayerLine) {
                    foreach ($layerLines as $layerLine) {
                        // Lines overlap.
                        if ($topLayerLine[0] == $layerLine[0] &&
                            $topLayerLine[1] == $layerLine[1]) {
                            continue;
                        }

                        if ($this->doLinesIntersect(
                            $topLayerLine[0][0], $topLayerLine[0][1],
                            $topLayerLine[1][0], $topLayerLine[1][1],
                            $layerLine[0][0], $layerLine[0][1],
                            $layerLine[1][0], $layerLine[1][1]
                        )
                        ) {
                            var_dump(array(                            $topLayerLine[0][0], $topLayerLine[0][1],
                                $topLayerLine[1][0], $topLayerLine[1][1],
                                $layerLine[0][0], $layerLine[0][1],
                                $layerLine[1][0], $layerLine[1][1]));
                            die();
                            return false;
                        }
                    }
                }
            }
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
                        $polygons[$polygon][] = array($x, $y);
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