<?php

namespace Etu\Core\CoreBundle\Home;

use Doctrine\ORM\EntityManager;
use Etu\Core\CoreBundle\Entity\Notification;
use Etu\Core\CoreBundle\Entity\Subscription;
use Etu\Core\CoreBundle\Framework\Definition\Module;
use Etu\Core\CoreBundle\Framework\Twig\GlobalAccessorObject;
use Etu\Core\UserBundle\Entity\Course;
use Etu\Core\UserBundle\Entity\User;
use Etu\Module\ArgentiqueBundle\Entity\Photo;
use Etu\Module\EventsBundle\Entity\Event;
use Etu\Module\UVBundle\Entity\Review;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Stopwatch\Stopwatch;

class HomeBuilder
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var GlobalAccessorObject
     */
    protected $globalAccessorObject;

    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @param EntityManager $manager
     * @param SecurityContext $context
     * @param GlobalAccessorObject $globalAccessorObject
     * @param Stopwatch $stopwatch
     */
    public function __construct(EntityManager $manager,
                                SecurityContext $context,
                                GlobalAccessorObject $globalAccessorObject,
                                Stopwatch $stopwatch)
    {
        $this->manager = $manager;
        $this->user = $context->getToken()->getUser();
        $this->globalAccessorObject = $globalAccessorObject;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return Course[]
     */
    public function getNextCourses()
    {
        $this->stopwatch->start('block_next_courses', 'home_blocks');

        /** @var Course[] $result */
        $result = $this->manager
            ->getRepository('EtuUserBundle:Course')
            ->getUserNextCourses($this->user);

        $this->stopwatch->stop('block_next_courses');

        return $result;
    }

    /**
     * @param Module[] $enabledModules
     * @return \Etu\Core\CoreBundle\Entity\Notification[]
     */
    public function getNotifications($enabledModules)
    {
        $this->stopwatch->start('block_notifications', 'home_blocks');

        $query = $this->manager->createQueryBuilder()
            ->select('n')
            ->from('EtuCoreBundle:Notification', 'n')
            ->where('n.authorId != :userId')
            ->setParameter('userId', $this->user->getId())
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(10);

        $this->stopwatch->start('block_notifications_subscriptions', 'home_blocks');

        /** @var $subscriptions Subscription[] */
        $subscriptions = $this->globalAccessorObject->get('notifs')->get('subscriptions');

        $this->stopwatch->stop('block_notifications_subscriptions');

        $subscriptionsWhere = [];

        /** @var $notifications Notification[] */
        $notifications = [];

        if (! empty($subscriptions)) {
            $this->stopwatch->start('block_notifications_filters', 'home_blocks');

            foreach ($subscriptions as $key => $subscription) {
                $subscriptionsWhere[] = '(n.entityType = :type_'.$key.' AND n.entityId = :id_'.$key.')';

                $query->setParameter('type_'.$key, $subscription->getEntityType());
                $query->setParameter('id_'.$key, $subscription->getEntityId());
            }

            if (! empty($subscriptionsWhere)) {
                $query = $query->andWhere(implode(' OR ', $subscriptionsWhere));
            }

            /*
             * Modules
             */
            $modulesWhere = array('n.module = \'core\'', 'n.module = \'user\'');

            foreach ($enabledModules as $module) {
                $identifier = $module->getIdentifier();
                $modulesWhere[] = 'n.module = :module_'.$identifier;

                $query->setParameter('module_'.$identifier, $identifier);
            }

            if (! empty($modulesWhere)) {
                $query = $query->andWhere(implode(' OR ', $modulesWhere));
            }

            $this->stopwatch->stop('block_notifications_filters');

            $this->stopwatch->start('block_notifications_query', 'home_blocks');

            $notifications = $query->getQuery()->getResult();

            $this->stopwatch->stop('block_notifications_query');
        }

        $this->stopwatch->stop('block_notifications');

        return $notifications;
    }

    /**
     * @return Review[]
     */
    public function getUvReviews()
    {
        $this->stopwatch->start('block_uvs_reviews', 'home_blocks');

        $query = $this->manager
            ->getRepository('EtuModuleUVBundle:Review')
            ->createQbReviewOf($this->user->getUvsList())
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery();

        $query->useResultCache(true, 1200);

        $result = $query->getResult();

        $this->stopwatch->stop('block_uvs_reviews');

        return $result;
    }

    /**
     * @return Event[]
     */
    public function getEvents()
    {
        $this->stopwatch->start('block_events', 'home_blocks');

        $query = $this->manager->createQueryBuilder()
            ->select('e, o')
            ->from('EtuModuleEventsBundle:Event', 'e')
            ->leftJoin('e.orga', 'o')
            ->where('e.begin >= :begin')
            ->setParameter('begin', new \DateTime())
            ->orderBy('e.begin', 'ASC')
            ->addOrderBy('e.end', 'ASC')
            ->setMaxResults(3)
            ->getQuery();

        $query->useResultCache(true, 1200);

        $result = $query->getResult();

        $this->stopwatch->stop('block_events');

        return $result;
    }

    /**
     * @return Photo[]
     */
    public function getPhotos()
    {
        $this->stopwatch->start('block_photos', 'home_blocks');

        $query = $this->manager->createQueryBuilder()
            ->select('p')
            ->from('EtuModuleArgentiqueBundle:Photo', 'p')
            ->orderBy('p.createdAt', 'DESC')
            ->where('p.ready = 1')
            ->setMaxResults(5)
            ->getQuery();

        $query->useResultCache(true, 1200);

        $result = $query->getResult();

        $this->stopwatch->stop('block_photos');

        return $result;
    }

    /**
     * @return User[]
     */
    public function getBirthdays()
    {
        $this->stopwatch->start('block_birthdays', 'home_blocks');

        $query = $this->manager->createQueryBuilder()
            ->select('u, m, o')
            ->from('EtuUserBundle:User', 'u')
            ->leftJoin('u.memberships', 'm')
            ->leftJoin('m.organization', 'o')
            ->where('DAY(u.birthday) = DAY(CURRENT_TIMESTAMP())')
            ->andWhere('MONTH(u.birthday) = MONTH(CURRENT_TIMESTAMP())')
            ->andWhere('u.birthday IS NOT NULL')
            ->andWhere('u.birthdayPrivacy = :privacy')
            ->setParameter('privacy', User::PRIVACY_PUBLIC)
            ->andWhere('u.id != :me')
            ->setParameter('me', $this->user->getId())
            ->getQuery();

        $query->useResultCache(true, 3600);

        /** @var User[] $users */
        $users = $query->getResult();

        // Find more interesting birthdays : same promotion (SRT4), same branch (SRT), others
        $usersWeights = [];

        foreach ($users as $key => $user) {
            $usersWeights[$key] = 0;

            if ($user->getBranch() == $this->user->getBranch()) {
                $usersWeights[$key]++;
            }

            if ($user->getNiveau() == $this->user->getNiveau()) {
                $usersWeights[$key]++;
            }
        }

        array_multisort(
            $usersWeights, SORT_DESC, SORT_NUMERIC,
            $users
        );

        $result = array_slice($users, 0, 3);

        $this->stopwatch->stop('block_birthdays');

        return $result;
    }
}