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
            $priceList = array_values($priceList)[0];
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
        $priceList = array_values($priceList)[0];

        if (is_null($priceList)){
            throw new HttpException(Response::HTTP_NOT_FOUND);
        }

        if ($request->get('export')){

            $phpExcelObject =  $this->get('phpexcel')->createPHPExcelObject();

            $phpExcelObject->getProperties()
                ->setCreator($priceList->getUser())
                ->setLastModifiedBy("Maarten Balliauw")
                ->setTitle($priceList->getCompany())
                ->setSubject("Office 2007 XLSX Test Document");

            $sheet = $phpExcelObject->setActiveSheetIndex(0);

            $sheet->getColumnDimension('A')->setWidth(50);
            $sheet->getColumnDimension('B')->setWidth(10);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(10);
            $sheet->getColumnDimension('E')->setWidth(15);

            $sheet->getDefaultStyle()->getAlignment()
                ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            $this->singleExport($priceList, $sheet, 1);

            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

            $response = $this->get('phpexcel')->createStreamedResponse($writer);
            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="'. $priceList->getCompany() . '_' . $priceList->getPerformDate()->format('Y:m:d') . '.xls"');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Cache-Control', 'maxage=1');

            return $response;
        }

        return ['priceList' => $priceList];
    }

    /**
     * @Route("/list-export", name="list_export")
     * @Method("POST")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function listExportAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $ids = $request->get('ids');

        if (count($ids) > 0){
            $ids = array_keys($ids);
            $priceLists = $em->getRepository('AppBundle:PriceList')->findWithRelations($ids);

            $phpExcelObject =  $this->get('phpexcel')->createPHPExcelObject();

            $phpExcelObject->getProperties()
                ->setCreator("Author")
                ->setLastModifiedBy("Maarten Balliauw")
                ->setTitle("Title")
                ->setSubject("Office 2007 XLSX Test Document");

            $sheet = $phpExcelObject->setActiveSheetIndex(0);

            $sheet->getColumnDimension('A')->setWidth(50);
            $sheet->getColumnDimension('B')->setWidth(10);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(10);
            $sheet->getColumnDimension('E')->setWidth(15);

            $sheet->getDefaultStyle()->getAlignment()
                ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            $lastRow = 1;
            foreach($priceLists as $priceList){
                $lastRow = $this->singleExport($priceList, $sheet, $lastRow);
                $lastRow += 5;
            }

            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

            $response = $this->get('phpexcel')->createStreamedResponse($writer);
            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename=export.xls');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Cache-Control', 'maxage=1');

            return $response;
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }

    /**
     * @param $priceList
     * @param $sheet
     * @param $startRow
     */
    private function singleExport($priceList, $sheet, $startRow)
    {
        $sheet->mergeCells("A$startRow:D$startRow")
            ->setCellValue("A$startRow", $priceList->getCompany() . ' '
                . $priceList->getPerformDate()->format('Y-m-d') . '    N:' . $priceList->getId());

        $sheet->getStyle("A$startRow")->getFont()->setBold(true);

        $sheet->setCellValue("E$startRow", PriceList::$BillingTypes[$priceList->getBillingType()]);
        $sheet->getStyle("E$startRow")->getFont()->setBold(true);

        for ($j = 65; $j <= 69; $j++) {
            $borders = $sheet->getStyle(chr($j) . $startRow)->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }

        $startRow++;

        $sheet->getStyle("A$startRow:E$startRow")->getFont()->setBold(true);

        $sheet
            ->setCellValue('A' . $startRow, 'Ապրանքի անվանում')
            ->setCellValue('B' . $startRow, 'Միավորի գին')
            ->setCellValue('C' . $startRow, 'Զեղչ')
            ->setCellValue('D' . $startRow, 'Քանակ')
            ->setCellValue('E' . $startRow, 'Արժեքը');


        for ($j = 65; $j <= 69; $j++) {
            $borders = $sheet->getStyle(chr($j) . $startRow)->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }

        $totalPrice = 0;
        $i = $startRow + 1;
        foreach($priceList->getPriceListProducts() as $priceListProduct){

            if ($priceListProduct->getQuantity() == 0){
                continue;
            }

            $price = $priceListProduct->getProduct()->getPrice() * $priceListProduct->getQuantity()
                * (100 - $priceListProduct->getDiscount()) / 100;

            $sheet
                ->setCellValue('A' . $i, $priceListProduct->getProduct()->getName())
                ->setCellValue('B' . $i, $priceListProduct->getProduct()->getPrice())
                ->setCellValue('C' . $i, $priceListProduct->getDiscount() . ($priceListProduct->getDiscount() ? '%' : ''))
                ->setCellValue('D' . $i, $priceListProduct->getQuantity())
                ->setCellValue('E' . $i, $price);

            for ($j = 65; $j <= 69; $j++) {
                $borders = $sheet->getStyle(chr($j) . $i)->getBorders();
                $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            }

            $i++;
            $totalPrice +=$price;
        }

        $sheet
            ->setCellValue('D' . $i, "Total Price")
            ->setCellValue('E' . $i, $totalPrice);

        $sheet->getStyle("D$i:E$i")->getFont()->setBold(true);

        $sheet->mergeCells("A$i:C$i")
            ->setCellValue("A$i", $priceList->getComment());

        for ($j = 65; $j <= 69; $j++) {
            $borders = $sheet->getStyle(chr($j) . $i)->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }

        return $i + 1;
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
