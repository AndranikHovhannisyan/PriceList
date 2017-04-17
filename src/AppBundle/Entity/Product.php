<?php
/**
 * Created by PhpStorm.
 * User: andranik
 * Date: 9/19/16
 * Time: 12:26 AM
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Product
 * @package AppBundle\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\ProductRepository")
 * @ORM\Table(name="product")
 */
class Product
{
    const ECONOMIC  = 1;
    const JUICE     = 2;

    public static $Types = [
        self::ECONOMIC => 'Տնտեսական',
        self::JUICE    => 'Հյութ'
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     */
    protected $enabled;

    /**
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @ORM\Column(name="price", type="float", nullable=false)
     */
    protected $price;

    /**
     * @ORM\Column(name="region_price", type="float", nullable=false)
     */
    protected $regionPrice;

    /**
     * @ORM\Column(name="type", type="smallint", nullable=true)
     */
    protected $type = self::ECONOMIC;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getname() ? $this->getname() : '';
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Product
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return mixed
     */
    public function getRegionPrice()
    {
        return $this->regionPrice;
    }

    /**
     * @param mixed $regionPrice
     */
    public function setRegionPrice($regionPrice)
    {
        $this->regionPrice = $regionPrice;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return Product::$Types[$this->getType()];
    }
}
