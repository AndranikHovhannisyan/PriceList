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
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="priceList")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     */
    protected $company;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\Column(name="perform_date", type="date", nullable=false)
     */
    protected $performDate;

    /**
     * @ORM\OneToMany(targetEntity="PriceListProduct", mappedBy="priceList", cascade={"persist", "remove"})
     */
    protected $priceListProducts;

    /**
     * @var
     */
    protected $total;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->priceListProducts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setPerformDate(new \DateTime());
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
     * @param mixed $performDate
     */
    public function setPerformDate($performDate)
    {
        $this->performDate = $performDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPerformDate()
    {
        return $this->performDate;
    }

    /**
     * Set company
     *
     * @param \AppBundle\Entity\Company $company
     *
     * @return PriceList
     */
    public function setCompany(\AppBundle\Entity\Company $company = null)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company
     *
     * @return \AppBundle\Entity\Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return mixed
     */
    public function getTotal()
    {
        return $this->total;
    }
}
