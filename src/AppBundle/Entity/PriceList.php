<?php
/**
 * Created by PhpStorm.
 * User: andranik
 * Date: 9/24/16
 * Time: 9:55 PM
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PriceList
 * @package AppBundle
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\PriceListRepository")
 * @ORM\Table(name="price_list")
 */
class PriceList
{
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\OneToMany(targetEntity="PriceListProduct", mappedBy="priceList", cascade={"persist", "remove"})
     */
    protected $priceListProducts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->priceListProducts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName() ? $this->getName() : '';
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
     * @return PriceList
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
     * Add priceListProduct
     *
     * @param \AppBundle\Entity\PriceListProduct $priceListProduct
     *
     * @return PriceList
     */
    public function addPriceListProduct(\AppBundle\Entity\PriceListProduct $priceListProduct)
    {
        $this->priceListProducts[] = $priceListProduct;

        return $this;
    }

    /**
     * Remove priceListProduct
     *
     * @param \AppBundle\Entity\PriceListProduct $priceListProduct
     */
    public function removePriceListProduct(\AppBundle\Entity\PriceListProduct $priceListProduct)
    {
        $this->priceListProducts->removeElement($priceListProduct);
    }

    /**
     * Get priceListProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPriceListProducts()
    {
        return $this->priceListProducts;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        $sum = 0;
        foreach($this->getPriceListProducts() as $plProduct){
            $sum += $plProduct->getQuantity() * $plProduct->getProduct()->getPrice();
        }

        return $sum;
    }
}
