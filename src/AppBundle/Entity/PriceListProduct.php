<?php
/**
 * Created by PhpStorm.
 * User: andranik
 * Date: 9/24/16
 * Time: 10:00 PM
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PriceListProduct
 * @package AppBundle\Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="price_list_product")
 */
class PriceListProduct
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="PriceList", inversedBy="priceListProducts")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id")
     *
     */
    protected $priceList;

    /**
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     *
     */
    protected $product;

    /**
     * @ORM\Column(name="discount", type="float", precision=10, scale=2, nullable=true)
     */
    protected $discount;

    /**
     * @ORM\Column(name="quantity", type="integer", nullable=false)
     */
    protected $quantity;

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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return PriceListProduct
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set priceList
     *
     * @param \AppBundle\Entity\PriceList $priceList
     *
     * @return PriceListProduct
     */
    public function setPriceList(\AppBundle\Entity\PriceList $priceList = null)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * Get priceList
     *
     * @return \AppBundle\Entity\PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Entity\Product $product
     *
     * @return PriceListProduct
     */
    public function setProduct(\AppBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }
}
