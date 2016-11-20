<?php

namespace AppBundle\Entity\Repository;

/**
 * PriceListRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PriceListRepository extends \Doctrine\ORM\EntityRepository
{
    public function findWithRelations($ids)
    {
        if (!is_array($ids)){
            $ids = [$ids];
        }

        return $this->getEntityManager()
            ->createQuery("SELECT pl, plp, p, c, u
                           FROM AppBundle:PriceList pl
                           INDEX BY pl.id
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           LEFT JOIN pl.company c
                           LEFT JOIN c.user u
                           WHERE pl.id IN (:ids)")
            ->setParameter('ids', $ids)
            ->getResult();
    }


    public function findQueryByUser($user, $companyId, $startDate, $endDate)
    {
        $startDate = $startDate ? $startDate->format('Y-m-d') : null;
        $endDate   = $endDate   ? $endDate->format('Y-m-d')   : null;

        return $this->getEntityManager()
            ->createQuery("SELECT pl, plp, p, c
                           FROM AppBundle:PriceList pl
                           INDEX BY pl.id
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           LEFT JOIN pl.company c
                           WHERE (pl.user = :user OR :user IS NULL)
                               AND (pl.company = :company OR :company IS NULL)
                               AND (pl.performDate >= :startDate OR :startDate IS NULL)
                               AND (pl.performDate <= :endDate OR :endDate IS NULL)
                           ORDER BY pl.id DESC")
            ->setParameter('user', $user)
            ->setParameter('company', $companyId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
    }

    public function findPriceListsTotal($priceListIds)
    {
        if (count($priceListIds) == 0){
            return [];
        }

        return $this->getEntityManager()
            ->createQuery("SELECT pl.id, SUM((CASE WHEN pl.isRegion = true THEN p.regionPrice ELSE p.price END) * plp.quantity * (100 - COALESCE(plp.discount, 0)) / 100) as total
                           FROM AppBundle:PriceList pl
                           INDEX BY pl.id
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           WHERE pl.id IN (:ids)
                           GROUP BY pl.id")
            ->setParameter('ids', $priceListIds)
            ->getResult();
    }


    public function findSaleDetails($userIds, $companyId, $productId, $startDate, $endDate)
    {
        $startDate = $startDate ? $startDate->format('Y-m-d') . ' 00:00:00' : null;
        $endDate = $endDate ? $endDate->format('Y-m-d') . ' 23:59:59' : null;
        $userIds = count($userIds) > 0 ? $userIds : [0];

        return $this->getEntityManager()
            ->createQuery("SELECT pl.isRegion, (CASE WHEN pl.isRegion = true THEN p.regionPrice ELSE p.price END) as price, p.name, pl.performDate, plp.discount, plp.quantity, (CASE WHEN pl.isRegion = true THEN p.regionPrice ELSE p.price END) * plp.quantity * (100 - COALESCE(plp.discount, 0)) / 100 as total
                           FROM AppBundle:priceList pl
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           WHERE ((:company IS NOT NULL AND pl.company = :company) OR pl.user IN (:userIds))
                           AND plp.quantity != 0 AND p.id = :product
                           AND (pl.performDate >= :startDate OR :startDate IS NULL)
                           AND (pl.performDate <= :endDate OR :endDate IS NULL)
                           ORDER BY pl.performDate DESC")
            ->setParameter('userIds', $userIds)
            ->setParameter('company', $companyId)
            ->setParameter('product', $productId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getResult();
    }


    public function findStatistic($userIds, $companyId, $startDate, $endDate)
    {
        $startDate = $startDate ? $startDate->format('Y-m-d') . ' 00:00:00' : null;
        $endDate = $endDate ? $endDate->format('Y-m-d') . ' 23:59:59' : null;
        $userIds = count($userIds) > 0 ? $userIds : [0];

        $result = $this->getEntityManager()
            ->createQuery("SELECT  p.id, p.name, pl.isRegion, (CASE WHEN pl.isRegion = true THEN p.regionPrice ELSE p.price END) as price, plp.discount, SUM(plp.quantity) as quantity
                           FROM AppBundle:priceList pl
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           WHERE ((:company IS NOT NULL AND pl.company = :company) OR pl.user IN (:userIds))
                           AND plp.quantity != 0
                           AND (pl.performDate >= :startDate OR :startDate IS NULL)
                           AND (pl.performDate <= :endDate OR :endDate IS NULL)
                           GROUP BY p.id, pl.isRegion, plp.discount
                           ORDER BY p.name")
            ->setParameter('userIds', $userIds)
            ->setParameter('company', $companyId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getResult();

        $products = [];
        foreach($result as $data){
            if (!isset($products[$data['id']])){
                $products[$data['id']] = $data;
                $products[$data['id']]['quantity'] = $data['quantity'] . ($data['isRegion'] ? 'մ' : '')  . ($data['discount'] ? "(-{$data['discount']}%) " : ' ');
                $products[$data['id']]['calculatedPrice'] = 0;
                $products[$data['id']]['allQuantity'] = $data['quantity'];
                $products[$data['id']]['count'] = 1;
            }
            else {
                $products[$data['id']]['quantity'] .= '+ ' . $data['quantity'] . ($data['isRegion'] ? 'մ' : '') . ($data['discount'] ? "(-{$data['discount']}%) " : ' ');
                $products[$data['id']]['allQuantity'] += $data['quantity'];
                $products[$data['id']]['count']++;
            }

            $products[$data['id']]['calculatedPrice'] += $data['price'] * $data['quantity'] * (100 - $data['discount']) / 100;
        }

        return $products;
    }

    public function findTableExport($ids)
    {
        if (count($ids) == 0){
            return [];
        }

        $result = $this->getEntityManager()
            ->createQuery("SELECT c.id as cid, c.name as company, p.id, p.name, pl.isRegion, (CASE WHEN pl.isRegion = true THEN p.regionPrice ELSE p.price END) as price,
                                  plp.discount, SUM(plp.quantity) as quantity
                           FROM AppBundle:PriceList pl
                           JOIN pl.priceListProducts plp
                           JOIN plp.product p
                           JOIN pl.company c
                           WHERE pl.id IN (:ids)
                           GROUP BY c.id, p.id, pl.isRegion, plp.discount
                           ORDER BY c.id, p.name")
            ->setParameter('ids', $ids)
            ->getResult();

        $companies = [];
        foreach($result as $value){
            if (!isset($companies[$value['cid']])){
                $companies[$value['cid']] = [];
            }

            if (!isset($companies[$value['cid']][$value['id']])){
                $companies[$value['cid']][$value['id']] = $value;
                if ($value['quantity']){
                    $companies[$value['cid']][$value['id']]['quantity'] = $value['quantity'] . ($value['isRegion'] ? 'մ' : '')  . ($value['discount'] ? "(-{$value['discount']}%) " : ' ');
                    $companies[$value['cid']][$value['id']]['count'] = 1;
                }
                $companies[$value['cid']][$value['id']]['allQuantity'] = $value['quantity'];
                $companies[$value['cid']][$value['id']]['calculatedPrice'] = 0;
            }
            else {
                if ($value['quantity']){
                    $q = $companies[$value['cid']][$value['id']]['quantity'];
                    $companies[$value['cid']][$value['id']]['quantity'] .= ($q ? '+ ' : '') . $value['quantity'] . ($value['isRegion'] ? 'մ' : '') . ($value['discount'] ? "(-{$value['discount']}%) " : ' ');
                    $companies[$value['cid']][$value['id']]['count']++;
                }
                $companies[$value['cid']][$value['id']]['allQuantity'] += $value['quantity'];
            }

            $companies[$value['cid']][$value['id']]['calculatedPrice'] += $value['price'] * $value['quantity'] * (100 - $value['discount']) / 100;
        }

        return $companies;
    }
}
