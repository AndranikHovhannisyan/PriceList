<?php
/**
 * Created by PhpStorm.
 * User: andranik
 * Date: 10/11/16
 * Time: 12:12 AM
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
class Company
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
     * @ORM\Column(name="address", type="string", length=200, nullable=false)
     */
    protected $address;

    /**
     * @ORM\OneToMany(targetEntity="PriceList", mappedBy="company")
     */
    protected $priceList;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->priceList = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Company
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
     * Set address
     *
     * @param string $address
     *
     * @return Company
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Add priceList
     *
     * @param \AppBundle\Entity\PriceList $priceList
     *
     * @return Company
     */
    public function addPriceList(\AppBundle\Entity\PriceList $priceList)
    {
        $this->priceList[] = $priceList;

        return $this;
    }

    /**
     * Remove priceList
     *
     * @param \AppBundle\Entity\PriceList $priceList
     */
    public function removePriceList(\AppBundle\Entity\PriceList $priceList)
    {
        $this->priceList->removeElement($priceList);
    }

    /**
     * Get priceList
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPriceList()
    {
        return $this->priceList;
    }
}
