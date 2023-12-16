<?php

namespace App\Tree;

use App\Tree\Output\OutputInterface;
use Exception;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChristmasTree
{
    private AACanvas $canvas;

    private ChristmasTreeDrawer $drawer;

    private bool $drawable;
    private array $chains;
    private array $glassBalls;
    private array $sweets;
    private array $lamps;
    private int|false|null $starColor;
    private array $gifts;
    private int $numberOfGifts;


    public function __construct(mixed $state = null, bool $drawable = true)
    {
        if (!is_array($state)) {
            $state = [];
        }
        $this->drawable = $drawable;
        $this->canvas = new AACanvas($drawable ? 60 : 1, $drawable ? 37 : 1);
        $this->drawer = new ChristmasTreeDrawer($this->canvas);

        $this->chains = $this->initializeObjectList(
            $this->drawer->getNumberOfChains(),
            $state["chains"] ?? null
        );
        $this->glassBalls = $this->initializeObjectList(
            $this->drawer->getNumberOfGlassBalls(),
            $state["glassBalls"] ?? null
        );
        $this->sweets = $this->initializeObjectList(
            $this->drawer->getNumberOfSweets(),
            $state["sweets"] ?? null
        );
        $this->lamps = $this->initializeObjectList(
            $this->drawer->getNumberOfLamps(),
            $state["lamps"] ?? null
        );
        $this->starColor = $this->normalizeColorValue(
            array_key_exists("starColor", $state) ? $state["starColor"] : false
        );
        $this->gifts = [];
        $this->numberOfGifts = $this->drawer->getNumberOfGifts();
        $gifts = $state['gifts'] ?? null;
        if (is_array($gifts)) {
            foreach ($gifts as $gift) {
                $this->putGiftLogical($gift);
            }
        }

        $this->redraw();
    }

    public function dumpState(): array
    {
        $state = [];
        $this->dumpStatePart($state, "chains", $this->chains);
        $this->dumpStatePart($state, "glassBalls", $this->glassBalls);
        $this->dumpStatePart($state, "sweets", $this->sweets);
        $this->dumpStatePart($state, "lamps", $this->lamps);
        $this->dumpStatePart($state, "starColor", $this->starColor);
        $this->dumpStatePart($state, "gifts", $this->gifts);
        return $state;
    }

    private function dumpStatePart(array &$state, string $key, mixed $value): self
    {
        if ($value === false) {
            return $this;
        }
        if (is_array($value)) {
            $found = false;
            foreach ($value as $val) {
                if ($val !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $this;
            }
        }
        $state[$key] = $value;
        return $this;
    }

    public function putChain(?int $color = null): self
    {
        $this->setObjectList($this->chains, null, $color);
        $this->drawChain(null);
        return $this;
    }

    public function putChainPart(int $partNumber, ?int $color = null): self
    {
        $this->setObjectList($this->chains, $partNumber, $color);
        $this->drawChain($partNumber);
        return $this;
    }

    public function removeChain(): self
    {
        $this->setObjectList($this->chains, null, false);
        $this->redraw();
        return $this;
    }

    public function removeChainPart(int $partNumber): self
    {
        $this->setObjectList($this->chains, $partNumber, false);
        $this->redraw();
        return $this;
    }

    public function getChain(): array
    {
        return $this->chains;
    }

    public function putGlassBalls(?int $color = null): self
    {
        $this->setObjectList($this->glassBalls, null, $color);
        $this->drawGlassBalls(null);
        return $this;
    }

    public function putGlassBallsPart(int $ballNumber, ?int $color = null): self
    {
        $this->setObjectList($this->glassBalls, $ballNumber, $color);
        $this->drawGlassBalls($ballNumber);
        return $this;
    }

    public function removeGlassBalls(): self
    {
        $this->setObjectList($this->glassBalls, null, false);
        $this->redraw();
        return $this;
    }

    public function removeGlassBallsPart(int $ballNumber): self
    {
        $this->setObjectList($this->glassBalls, $ballNumber, false);
        $this->redraw();
        return $this;
    }

    public function getGlassBalls(): array
    {
        return $this->glassBalls;
    }

    public function putSweets(?int $color = null): self
    {
        $this->setObjectList($this->sweets, null, $color);
        $this->drawSweets(null);
        return $this;
    }

    public function putSweetsPart(int $sweetNumber, ?int $color = null): self
    {
        $this->setObjectList($this->sweets, $sweetNumber, $color);
        $this->drawSweets($sweetNumber);
        return $this;
    }

    public function removeSweets(): self
    {
        $this->setObjectList($this->sweets, null, false);
        $this->redraw();
        return $this;
    }

    public function removeSweetsPart(int $sweetNumber): self
    {
        $this->setObjectList($this->sweets, $sweetNumber, false);
        $this->redraw();
        return $this;
    }

    public function getSweets(): array
    {
        return $this->sweets;
    }

    public function putLamps(?int $color = null): self
    {
        $this->setObjectList($this->lamps, null, $color);
        $this->drawLamps(null);
        return $this;
    }

    public function putLampsPart(int $lampNumber, ?int $color = null): self
    {
        $this->setObjectList($this->lamps, $lampNumber, $color);
        $this->drawLamps($lampNumber);
        return $this;
    }

    public function removeLamps(): self
    {
        $this->setObjectList($this->lamps, null, false);
        $this->redraw();
        return $this;
    }

    public function removeLampsPart(int $lampNumber): self
    {
        $this->setObjectList($this->lamps, $lampNumber, false);
        $this->redraw();
        return $this;
    }

    public function getLamps(): array
    {
        return $this->lamps;
    }

    public function putStar(?int $color = null): self
    {
        $this->starColor = $color;
        $this->drawStar();
        return $this;
    }

    public function getStar(): int|false|null
    {
        return $this->starColor;
    }

    public function removeStar(): self
    {
        $this->starColor = false;
        $this->redraw();
        return $this;
    }

    public function putGift(string $label, ?int $packageColor = null, ?int $labelColor = null): self
    {
        $this->putGiftLogical([
            'label' => $label,
            'packageColor' => $packageColor,
            'labelColor' => $labelColor,
        ]);
        $this->drawGifts();
        return $this;
    }

    private function putGiftLogical(mixed $giftDescriptor): self
    {
        if (!is_array($giftDescriptor) || !array_key_exists('label', $giftDescriptor)) {
            return $this;
        }
        if (!array_key_exists('packageColor', $giftDescriptor) || !array_key_exists('labelColor', $giftDescriptor)) {
            return $this;
        }
        if (!is_string($giftDescriptor['label'])) {
            return $this;
        }
        if (!is_null($giftDescriptor['packageColor']) && !is_int($giftDescriptor['packageColor'])) {
            return $this;
        }
        if (!is_null($giftDescriptor['labelColor']) && !is_int($giftDescriptor['labelColor'])) {
            return $this;
        }
        $this->gifts[] = [
            'label' => $giftDescriptor['label'],
            'packageColor' => $giftDescriptor['packageColor'],
            'labelColor' => $giftDescriptor['labelColor'],
        ];
        while (count($this->gifts) > $this->numberOfGifts) {
            array_shift($this->gifts);
        }
        return $this;
    }

    public function removeGifts(): self
    {
        $this->gifts = [];
        $this->redraw();
        return $this;
    }

    public function removeGift(int $giftNumber): self
    {
        if (isset($this->gifts[$giftNumber])) {
            array_splice($this->gifts, $giftNumber, 1);
        }
        $this->redraw();
        return $this;
    }

    private function initializeObjectList(int $number, mixed $initialValue): array
    {
        if (!is_array($initialValue)) {
            $initialValue = [];
        }
        $list = [];
        for ($i = 0; $i < $number; $i++) {
            if (array_key_exists($i, $initialValue)) {
                $value = $this->normalizeColorValue($initialValue[$i]);
            } else {
                $value = false;
            }
            $list[] = $value;
        }
        return $list;
    }

    private function normalizeColorValue(mixed $value): int|false|null
    {
        if (!is_int($value) && !is_null($value)) {
            return false;
        }
        return $value;
    }

    private function iterateObjectList(array &$list, ?int $number): iterable
    {
        if ($number === null) {
            foreach ($list as $i => $v) {
                yield $i => $v;
            }
        } else {
            $number = $this->modulo($number, count($list));
            yield $number => $list[$number];
        }
    }

    private function setObjectList(array &$list, ?int $number, mixed $value): array
    {
        if ($number === null) {
            foreach (array_keys($list) as $i) {
                $list[$i] = $value;
            }
        } else {
            $number = $this->modulo($number, count($list));
            $list[$number] = $value;
        }
        return $list;
    }

    private function modulo(int $a, int $b): int
    {
        if ($a < 0) {
            return $b - ((-$a) % $b);
        } else {
            return $a % $b;
        }
    }

    private function getSafeColor(?int $color): ?int
    {
        if ($color === null) {
            return null;
        }
        return $this->modulo($color, 16);
    }


    private function redraw(): self
    {
        if ($this->drawable) {
            $this->canvas->clear();
            $this->drawTree();
            $this->drawChain();
            $this->drawGlassBalls();
            $this->drawSweets();
            $this->drawLamps();
            $this->drawStar();
            $this->drawGifts();
        }
        return $this;
    }

    private function drawGifts(): self
    {
        if ($this->drawable) {
            foreach ($this->gifts as $i => $giftDescriptor) {
                $this->canvas->setColor($this->getSafeColor($giftDescriptor['packageColor']));
                $this->drawer->drawGift($i);
                $this->canvas->setColor($this->getSafeColor($giftDescriptor['labelColor']));
                $this->drawer->drawGiftLabel($giftDescriptor['label'], $i);
            }
        }
        return $this;
    }

    private function drawTree(): self
    {
        if ($this->drawable) {
            $this->canvas->setColor(2);
            $this->drawer->drawTree();
            $this->canvas->setColor(3);
            $this->drawer->drawRoot();
            $this->canvas->setColor(null);
        }
        return $this;
    }

    private function drawChain(?int $number = null): self
    {
        if ($this->drawable) {
            foreach ($this->iterateObjectList($this->chains, $number) as $i => $color) {
                if ($color !== false) {
                    $this->canvas->setColor($this->getSafeColor($color));
                    $this->drawer->drawChain($i);
                }
            }
            $this->canvas->setColor(null);
        }
        return $this;
    }

    private function drawGlassBalls(?int $number = null): self
    {
        if ($this->drawable) {
            foreach ($this->iterateObjectList($this->glassBalls, $number) as $i => $color) {
                if ($color !== false) {
                    $this->canvas->setColor($this->getSafeColor($color));
                    $this->drawer->drawGlassBalls($i);
                }
            }
            $this->canvas->setColor(null);
        }
        return $this;
    }

    private function drawSweets(?int $number = null): self
    {
        if ($this->drawable) {
            foreach ($this->iterateObjectList($this->sweets, $number) as $i => $color) {
                if ($color !== false) {
                    $this->canvas->setColor($this->getSafeColor($color));
                    $this->drawer->drawSweets($i);
                }
            }
            $this->canvas->setColor(null);
        }
        return $this;
    }

    private function drawLamps(?int $number = null): self
    {
        if ($this->drawable) {
            foreach ($this->iterateObjectList($this->lamps, $number) as $i => $color) {
                if ($color !== false) {
                    $this->canvas->setColor($this->getSafeColor($color));
                    $this->drawer->drawLamps($i);
                }
            }
            $this->canvas->setColor(null);
        }
        return $this;
    }

    private function drawStar(): self
    {
        if ($this->drawable) {
            if ($this->starColor !== false) {
                $this->canvas->setColor($this->getSafeColor($this->starColor));
                $this->drawer->drawStar();
                $this->canvas->setColor(null);
            }
        }
        return $this;
    }

    public function render(OutputInterface $output): self
    {
        if (!$this->drawable) {
            throw new Exception("Cannot render a non-drawable christmas tree");
        }
        $this->canvas->render($output);
        return $this;
    }

    public function clearOutput(OutputInterface $output): self
    {
        if (!$this->drawable) {
            throw new Exception("Cannot render a non-drawable christmas tree");
        }
        $this->canvas->clearOutput($output);
        return $this;
    }
}
