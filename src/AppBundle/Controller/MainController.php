<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PriceList;
use AppBundle\Entity\PriceListProduct;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\PriceListType;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $companies = $em->getRepository('AppBundle:Company')->findAllIndexedById();

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
            $priceList = $em->getRepository('AppBundle:PriceList')->findWithRelations($id);
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

        return array('form' => $form->createView(), 'companies' => $companies);
    }

    /**
     * @Route("/view/{id}", name="view", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_USER')")
     * @Template()
     *
     * @param Request $request
     * @param $id
     * @return array
     */
    public function viewAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:User')->findAll();

        $userId    = $request->get('userId', null);
        $startDate = $request->get('start_date', null);
        $endDate   = $request->get('end_date', null);

        $priceList = $em->getRepository('AppBundle:PriceList')->findWithRelations($id);

        if (is_null($priceList)){
            throw new HttpException(Response::HTTP_NOT_FOUND);
        }

        return ['priceList' => $priceList, 'users' => $users];
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
        $priceListsQuery = $em->getRepository('AppBundle:PriceList')->findQueryByUser($user);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($priceListsQuery, $request->query->getInt('page', 1), 15);

        $totals = $em->getRepository('AppBundle:PriceList')->findPriceListsTotal(array_keys($pagination->getItems()));



        foreach($pagination->getItems() as $id => &$priceList){
            $priceList->setTotal($totals[$id]['total']);
        }

        return ['priceLists' => $pagination];
    }

    /**
     * @Route("/statistic", name="statistic")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function statisticAction(Request $request)
    {
        $em        = $this->getDoctrine()->getManager();
        $companies = $em->getRepository('AppBundle:Company')->findAllIndexedById();
        $result    = [];

        $companyId = $request->get('company', -1);
        $startDate = $request->get('start_date', null);
        $endDate   = $request->get('end_date', null);

        if ($request->getMethod() == "POST"){
            if(is_null($companyId) || !isset($companies[$companyId])){
                throw new HttpException(Response::HTTP_BAD_REQUEST);
            }

            $result = $em->getRepository('AppBundle:PriceList')->findStatistic($companyId, $startDate, $endDate);
        }

        return [
            'companyId' => $companyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'companies' => $companies,
            'result' => $result
        ];
    }
}
