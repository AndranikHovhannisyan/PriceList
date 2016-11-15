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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\PriceListType;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class MainController extends Controller
{
    /**
     * @Route("/create/{id}", name="single", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function createAction(Request $request, $id = null)
    {
        $user = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $companies = $em->getRepository('AppBundle:Company')->findAllIndexedById($user);
        $products = $em->getRepository('AppBundle:Product')->findAllIndexedById();

        if (is_null($id)){
            $priceList = new PriceList();
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

            $zeroProductIds = $request->get('zero_products');
            $zeroProductCounts = $request->get('zero_products_count');

            foreach($zeroProductIds as $key => $zeroProductId){
                if (!$zeroProductId || !isset($products[$zeroProductId]) || !$zeroProductCounts[$key]){
                    continue;
                }

                $priceListProduct = new PriceListProduct();
                $priceListProduct->setProduct($products[$zeroProductId]);
                $priceListProduct->setQuantity($zeroProductCounts[$key]);
                $priceListProduct->setDiscount(100);
                $priceListProduct->setPriceList($priceList);
                $priceList->addPriceListProduct($priceListProduct);
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
            $response->headers->set('Content-Disposition', 'attachment; filename="'. $priceList->getCompany() . '_' . $priceList->getPerformDate()->format('d-m-Y') . '.xls"');
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
                . $priceList->getPerformDate()->format('d-m-Y') . '    N:' . $priceList->getId());

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

        $zeroPriceListProducts = $priceList->getZeroPriceListProducts();
        $totalPrice = 0;
        $i = $startRow + 1;
        foreach($priceList->getPriceListProducts() as $priceListProduct){

            if ($priceListProduct->getQuantity() == 0 || $priceListProduct->getDiscount() == 100){
                continue;
            }

            $price = $priceListProduct->getProduct()->getPrice() * $priceListProduct->getQuantity()
                * (100 - $priceListProduct->getDiscount()) / 100;

            $zeroCount = 0;
            if (isset($zeroPriceListProducts[$priceListProduct->getProduct()->getId()])){
                $zeroCount = $zeroPriceListProducts[$priceListProduct->getProduct()->getId()]->getQuantity();
            }

            $sheet
                ->setCellValue('A' . $i, $priceListProduct->getProduct()->getName())
                ->setCellValue('B' . $i, $priceListProduct->getProduct()->getPrice())
                ->setCellValue('C' . $i, $priceListProduct->getDiscount() . ($priceListProduct->getDiscount() ? '%' : ''))
                ->setCellValue('D' . $i, $priceListProduct->getQuantity() . ($zeroCount ? ' + ' . $zeroCount . '(-100%)' : ""))
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
        $em        = $this->getDoctrine()->getManager();
        $user      = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        $users     = $em->getRepository('AppBundle:User')->findAll();
        $companies = $em->getRepository('AppBundle:Company')->findAllIndexedById($user);

        $userId    = $request->get('user', null);
        $companyId = $request->get('company', null);
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');

        $userId    = $userId    ? $userId    : null;
        $companyId = $companyId ? $companyId : null;
        $startDate = $startDate ? new \DateTime($startDate) : null;
        $endDate   = $endDate   ? new \DateTime($endDate)   : null;

        $user = $this->isGranted('ROLE_ADMIN') ? $userId : $this->getUser();
        $priceListsQuery = $em->getRepository('AppBundle:PriceList')->findQueryByUser($user, $companyId, $startDate, $endDate);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate($priceListsQuery, $request->query->getInt('page', 1), 15);

        $totals = $em->getRepository('AppBundle:PriceList')->findPriceListsTotal(array_keys($pagination->getItems()));



        foreach($pagination->getItems() as $id => &$priceList){
            $priceList->setTotal($totals[$id]['total']);
        }

        return [
            'priceLists'  => $pagination,
            'users'       => $users,
            'companies'   => $companies,
            'companyId'   => $companyId,
            'user_id'     => $userId,
            'start_date'  => $startDate,
            'end_date'    => $endDate
        ];
    }

    /**
     * @Route("/statistic", name="statistic")
     * @Route("/sale-details", name="sale_details")
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
        $productId = $request->get('product', null);
        $startDate = $request->get('start_date', null);
        $endDate   = $request->get('end_date', null);

        $userId    = $userId    ? $userId    : null;
        $companyId = $companyId ? $companyId : null;
        $productId = $productId ? $productId : null;
        $startDate = $startDate ? new \DateTime($startDate) : null;
        $endDate   = $endDate   ? new \DateTime($endDate)   : null;

        if (true || $request->getMethod() == "POST"){
            if (is_null($companyId) && is_null($userId)){
                return [
                    'companyId'  => null,
                    'userId'     => null,
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'companies'  => $companies,
                    'users'      => $users,
                    'result'     => []
                ];
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

            if ($request->get('_route') == "sale_details"){
                $result = $em->getRepository('AppBundle:PriceList')->findSaleDetails($userId, $companyId, $productId, $startDate, $endDate);

                return new JsonResponse($result);
            }

            $result = $em->getRepository('AppBundle:PriceList')->findStatistic($userId, $companyId, $startDate, $endDate);

            if ($request->get('export_btn')){
                return $this->exportStatistic($result, isset($companies[$companyId]) ? $companies[$companyId] : null,
                                      isset($users[$userId]) ? $users[$userId] : null,
                                      $startDate ? $startDate->format('d-m-Y') : null, $endDate ? $endDate->format('d-m-Y') : null);
            }
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

    private function exportStatistic($result, $company, $user, $startDate, $endDate)
    {
        $phpExcelObject =  $this->get('phpexcel')->createPHPExcelObject();

        $phpExcelObject->getProperties()
            ->setCreator("Author")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Title")
            ->setSubject("Office 2007 XLSX Test Document");

        $sheet = $phpExcelObject->setActiveSheetIndex(0);

        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);

        $sheet->getDefaultStyle()->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT)
            ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
            ->setWrapText(true);



        $sheet->mergeCells("A1:D1")
            ->setCellValue("A1", $company . " " . $user . " " . $startDate . (($startDate || $endDate) ? "->" : "") . $endDate);

        $sheet->getStyle("A1")->getFont()->setBold(true);

        for ($j = 65; $j <= 68; $j++) {
            $borders = $sheet->getStyle(chr($j) . '1')->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }

        $sheet->getStyle("A1:D1")->getFont()->setBold(true);

        $sheet
            ->setCellValue('A2', 'Ապրանքի անվանում')
            ->setCellValue('B2', 'Միավորի գին')
            ->setCellValue('C2', 'Քանակ')
            ->setCellValue('D2', 'Արժեքը');


        for ($j = 65; $j <= 68; $j++) {
            $borders = $sheet->getStyle(chr($j) . '2')->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }

        $totalPrice = 0;
        $i = 3;
        foreach($result as $data){

            $sheet
                ->setCellValue('A' . $i, $data['name'])
                ->setCellValue('B' . $i, $data['price'])
                ->setCellValue('C' . $i, $data['quantity'] . ($data['count'] > 1 ? ' = ' . $data['allQuantity'] : ''))
                ->setCellValue('D' . $i, $data['calculatedPrice']);

            for ($j = 65; $j <= 68; $j++) {
                $borders = $sheet->getStyle(chr($j) . $i)->getBorders();
                $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
                $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            }

            $i++;
            $totalPrice += $data['calculatedPrice'];
        }

        $sheet
            ->setCellValue('C' . $i, "Total Price")
            ->setCellValue('D' . $i, $totalPrice);

        $sheet->getStyle("D$i:E$i")->getFont()->setBold(true);

        $sheet->mergeCells("A$i:C$i");

        for ($j = 65; $j <= 68; $j++) {
            $borders = $sheet->getStyle(chr($j) . $i)->getBorders();
            $borders->getTop()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getBottom()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getLeft()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
            $borders->getRight()->setBorderStyle(\PHPExcel_Style_Border::BORDER_MEDIUM);
        }


        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=export.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;
    }


    /**
     * @Route("/add-company", name="add_company")
     * @Method("POST")
     * @Security("has_role('ROLE_USER')")
     * @Template()
     */
    public function createCompanyAction(Request $request)
    {
        if ($this->isGranted('ROLE_ADMIN') || !($name = $request->get('company_name', null))){
            throw new HttpException(Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $company = new Company();
        $company->setUser($this->getUser());
        $company->setName($name);

        $em->persist($company);
        $em->flush();

        return $this->redirectToRoute('single');
    }

    /**
     * This action use to upload data of cr data and po data from excel to project
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/database", name="database")
     * @Security("has_role('ROLE_ADMIN')")
     * @Template()
     */
    public function databaseAction(Request $request)
    {
        return [];
    }


    /**
     * This action use to upload data of cr data and po data from excel to project
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/export-database", name="export_database")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function exportDatabaseAction(Request $request)
    {
        $databaseName = $this->getParameter('database_name');
        $databaseUser = $this->getParameter('database_user');
        $databasePassword = $this->getParameter('database_password');
        $filename = "../pl.sql";

        $cmp = "mysqldump --user=$databaseUser  --host=localhost --password=$databasePassword $databaseName > $filename";

        $process = new Process($cmp);
        $process->run();

        $response = new Response();

        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', mime_content_type($filename));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filename) . '";');
        $response->headers->set('Content-length', filesize($filename));
        $response->sendHeaders();
        $response->setContent(file_get_contents($filename));

        return $response;
    }
}
