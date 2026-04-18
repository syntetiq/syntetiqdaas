<?php

namespace SyntetiQ\Bundle\OmniverseBundle\Entity\Value;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Vector3
{
    #[ORM\Column(type: Types::FLOAT)]
    private float $x;

    #[ORM\Column(type: Types::FLOAT)]
    private float $y;

    #[ORM\Column(type: Types::FLOAT)]
    private float $z;

    public function __construct(float $x = 0, float $y = 0, float $z = 0)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function getX(): float { return $this->x; }
    public function getY(): float { return $this->y; }
    public function getZ(): float { return $this->z; }

    public function setX(float $x): self { $this->x = $x; return $this; }
    public function setY(float $y): self { $this->y = $y; return $this; }
    public function setZ(float $z): self { $this->z = $z; return $this; }

    public function toArray(): array
    {
        return [$this->x, $this->y, $this->z];
    }
}
