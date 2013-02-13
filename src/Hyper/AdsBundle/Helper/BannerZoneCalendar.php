<?php

namespace Hyper\AdsBundle\Helper;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Hyper\AdsBundle\Entity\Zone;

class BannerZoneCalendar
{
    const DATE_FORMAT = 'Y-m-d';

    const CACHE_PREFIX = 'cal_';
    const CACHE_ALL_PREFIX = 'all_set_';

    /** @var \Doctrine\Common\Cache\CacheProvider */
    private $cache;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    public function __construct(CacheProvider $cache, EntityManager $em)
    {
        $this->cache = $cache;
        $this->em = $em;
    }

    public function test()
    {
        $this->cache->save('dupa', 'cipa');
    }

    /**
     * @param \Hyper\AdsBundle\Entity\Zone $zone
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return \DateTime[]
     */
    public function getDaysInCommonWithZone(Zone $zone, \DateTime $from, \DateTime $to)
    {
        if (!$this->cache->contains(self::CACHE_ALL_PREFIX . $zone->getId())) {
            $this->warmUp($zone);
        }

        $oneDayInterval = new \DateInterval('P1D');
        $period = new \DatePeriod($from, $oneDayInterval, $to);

        $zoneId = $zone->getId();
        $commonDays = array();
        foreach ($period as $date) {
            $dateString = $date->format(self::DATE_FORMAT);
            $cacheId = self::CACHE_PREFIX . $zoneId . '_' . $dateString;
            if (($value = $this->cache->fetch($cacheId)) && $value > 3) {
                $commonDays[$dateString] = $date;
            }
        }

        return $commonDays;
    }

    private function warmUp(Zone $zone)
    {
        /** @var $orderRepository \Hyper\AdsBundle\Entity\OrderRepository */
        $orderRepository = $this->em->getRepository('HyperAdsBundle:Order');
        $ordersInZone = $orderRepository->getOrdersForZone($zone);

        $dailyInterval = new \DateInterval('P1D');
        $days = array();
        foreach ($ordersInZone as $order) {
            $period = new \DatePeriod($order->getPaymentFrom(), $dailyInterval, $order->getPaymentTo());
            $this->insertBannersDays($period, $days);
        }

        $this->saveDaysInCache($days, $zone->getId());
    }

    private function saveDaysInCache($days, $zoneId)
    {
        foreach ($days as $dayString => $numOfBanners) {
            $cacheId = self::CACHE_PREFIX . $zoneId . '_' . $dayString;
            $this->cache->save($cacheId, $numOfBanners);
        }
        $this->cache->save(self::CACHE_ALL_PREFIX . $zoneId, true);
    }

    private function insertBannersDays($period, &$days)
    {
        foreach ($period as $date) {
            /** @var $date \DateTime */
            $dateString = $date->format(self::DATE_FORMAT);
            if (!array_key_exists($dateString, $days)) {
                $days[$dateString] = 1;
            } else {
                $days[$dateString]++;
            }
        }
    }
}
