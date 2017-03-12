<?php

namespace AppBundle\DataFixtures\ORM;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use UserBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    private $container;

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // TODO: Implement load() method.
        $this->addUser($manager, 'admin', 'admin@masterlube.com', 'password', 'Cristian', 'Merli',
            ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $this->addUser($manager, 'user1', 'user1@masterlube.com', 'password', 'Jack', 'Bauer', ['ROLE_ADMIN']);
        $this->addUser($manager, 'user2', 'user2@masterlube.com', 'password', 'Walter', 'White');
        $this->addUser($manager, 'user3', 'user3@masterlube.com', 'password', 'Jim', 'Gordon');
        $this->addUser($manager, 'user4', 'user4@masterlube.com', 'password', 'Sydney', 'Bristow');
        $this->addUser($manager, 'user5', 'user5@masterlube.com', 'password', 'Vick', 'Mackey');
    }

    /**
     * @param ObjectManager $manager
     * @param               $username
     * @param               $email
     * @param               $password
     * @param               $firstName
     * @param               $lastName
     * @param array $roles
     */
    private function addUser(
        ObjectManager $manager,
        $username,
        $email,
        $password,
        $firstName,
        $lastName,
        $roles = ['ROLE_USER']
    ) {
        $userManager = $this->container->get('fos_user.user_manager');
        $user = new User();
        $user->setUsername($username);
        $user->setUsernameCanonical($username);
        $user->setEmail($email);
        $user->setEmailCanonical($email);
        $user->setPlainPassword($password);
        $userManager->updatePassword($user);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setEnabled(true);
        //$userManager->updateUser($user, true);
        $manager->persist($user);
        $manager->flush();
        $this->addReference($username, $user);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}