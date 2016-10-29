<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Company;
use AppBundle\Entity\PriceList;
use AppBundle\Entity\PriceListProduct;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function viewAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $priceList = $em->getRepository('AppBundle:PriceList')->findWithRelations($id);

        if (is_null($priceList)){
            throw new HttpException(Response::HTTP_NOT_FOUND);
        }

        return ['priceList' => $priceList];
    }

    /**
     * @Route("/", name="list")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:User')->findAll();

        $userId    = $request->get('user', null);
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');

        $userId    = $userId    ? $userId    : null;
        $startDate = $startDate ? $startDate : null;
        $endDate   = $endDate   ? $endDate   : null;

        $user = $this->isGranted('ROLE_ADMIN') ? $userId : $this->getUser();
        $priceListsQuery = $em->getRepository('AppBundle:PriceList')->findQueryByUser($user, $startDate, $endDate);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($priceListsQuery, $request->query->getInt('page', 1), 15);

        $totals = $em->getRepository('AppBundle:PriceList')->findPriceListsTotal(array_keys($pagination->getItems()));



        foreach($pagination->getItems() as $id => &$priceList){
            $priceList->setTotal($totals[$id]['total']);
        }

        return [
            'priceLists'  => $pagination,
            'users'       => $users,
            'user_id'     => $userId,
            'start_date'  => $startDate,
            'end_date'    => $endDate
        ];
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
        $users     = $em->getRepository('AppBundle:User')->findAllIndexedById();
        $result    = [];

        $userId    = $request->get('user', null);
        $companyId = $request->get('company', null);
        $startDate = $request->get('start_date', null);
        $endDate   = $request->get('end_date', null);

        $userId    = $userId    ? $userId    : null;
        $companyId = $companyId ? $companyId : null;
        $startDate = $startDate ? $startDate : null;
        $endDate   = $endDate   ? $endDate   : null;

        if ($request->getMethod() == "POST"){
            if (is_null($companyId) && is_null($userId)){
                throw new HttpException(Response::HTTP_BAD_REQUEST);
            }

            if (!is_null($companyId) && !is_null($userId)){
                throw new HttpException(Response::HTTP_BAD_REQUEST);
            }

            if(!is_null($companyId) && !isset($companies[$companyId])){
                throw new HttpException(Response::HTTP_BAD_REQUEST);
            }

            if(!is_null($userId) && !isset($users[$userId])){
                throw new HttpException(Response::HTTP_BAD_REQUEST);
            }

            $result = $em->getRepository('AppBundle:PriceList')->findStatistic($userId, $companyId, $startDate, $endDate);
        }

        return [
            'companyId'  => $companyId,
            'userId'     => $userId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'companies'  => $companies,
            'users'      => $users,
            'result'     => $result
        ];
    }


    /**
     * @Route("/add-company", name="add_company")
     * @Method("POST")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function createCompanyAction(Request $request)
    {
        if (!($name = $request->get('company_name', null))){
            throw new HttpException(Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $company = new Company();
        $company->setName($name);

        $em->persist($company);
        $em->flush();

        return $this->redirectToRoute('single');
    }
}
