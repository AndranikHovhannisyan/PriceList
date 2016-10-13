<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PriceList;
use AppBundle\Entity\PriceListProduct;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\PriceListType;

class MainController extends Controller
{
    /**
     * @Route("/create/{id}", name="single", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function createAction(Request $request, $id = null)
    {
        $em = $this->getDoctrine()->getManager();

        if (is_null($id)){
            $priceList = new PriceList();
            $products = $em->getRepository('AppBundle:Product')->findAll();
            foreach($products as $product){
                $priceListProduct = new PriceListProduct();
                $priceListProduct->setProduct($product);
                $priceListProduct->setQuantity(0);
                $priceList->addPriceListProduct($priceListProduct);
            }
        }
        else {
            $priceList = $em->getRepository('AppBundle:PriceList')->find($id);
        }

        $form = $this->createForm(PriceListType::class, $priceList);

        $form->handleRequest($request);

        if ($form->isValid()){
            $priceList->setUser($this->getUser());

            foreach($priceList->getPriceListProducts() as $priceListProduct){
                $priceListProduct->setPriceList($priceList);
            }

            $em->persist($priceList);
            $em->flush();

            return $this->redirectToRoute('list');
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/", name="list")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        $priceLists = $em->getRepository('AppBundle:PriceList')->findByUser($user);
        $totals = $em->getRepository('AppBundle:PriceList')->findPriceListsTotal(array_keys($priceLists));

        foreach($priceLists as $id => $priceList){
            $priceList->setTotal($totals[$id]['total']);
        }

        return ['priceLists' => $priceLists];
    }

    /**
     * @Route("/statistic", name="statistic")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function statisticAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $companies = $em->getRepository('AppBundle:Company')->findAll();

        return ['companies' => $companies];
    }
}
