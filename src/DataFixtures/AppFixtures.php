<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Client;
use App\Entity\Privilege;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Privileges
        $privileges = $this->createPrivileges($manager);
        
        // Create Roles with their privileges
        $roles = $this->createRoles($manager, $privileges);
        
        // Create Users
        $this->createUsers($manager, $roles);
        
        // Create Sample Clients
        $this->createClients($manager);
        
        // Create Sample Articles
        $this->createArticles($manager);
        
        $manager->flush();
    }

    private function createPrivileges(ObjectManager $manager): array
    {
        $privilegesData = [
            // Client management
            ['CLIENT_MANAGE', 'Gérer les clients (créer, modifier, activer/désactiver)'],
            ['CLIENT_VIEW', 'Consulter les clients'],
            
            // Article management
            ['ARTICLE_MANAGE', 'Gérer les articles (créer, modifier, activer/désactiver)'],
            ['ARTICLE_VIEW', 'Consulter les articles'],
            
            // Sales Orders
            ['ORDER_CREATE', 'Créer des bons de commande'],
            ['ORDER_EDIT', 'Modifier des bons de commande'],
            ['ORDER_CANCEL', 'Annuler des bons de commande'],
            ['ORDER_VIEW', 'Consulter des bons de commande'],
            
            // Delivery Notes
            ['DELIVERY_CREATE', 'Créer des bons de livraison'],
            ['DELIVERY_CANCEL', 'Annuler des bons de livraison'],
            ['DELIVERY_VIEW', 'Consulter des bons de livraison'],
            
            // Invoices
            ['INVOICE_CREATE', 'Créer des factures'],
            ['INVOICE_CANCEL', 'Annuler des factures'],
            ['INVOICE_VIEW', 'Consulter des factures'],
            
            // Payments
            ['PAYMENT_CREATE', 'Enregistrer des paiements'],
            ['PAYMENT_CANCEL', 'Annuler des paiements'],
            ['PAYMENT_VIEW', 'Consulter des paiements'],
            
            // Administration
            ['USER_MANAGE', 'Gérer les utilisateurs'],
            ['ROLE_MANAGE', 'Gérer les rôles et privilèges'],
            ['AUDIT_VIEW', 'Consulter le journal d\'audit'],
            
            // System
            ['SYSTEM_ADMIN', 'Accès administrateur complet'],
        ];

        $privileges = [];
        foreach ($privilegesData as [$code, $description]) {
            $privilege = new Privilege();
            $privilege->setCode($code);
            $privilege->setDescription($description);
            $manager->persist($privilege);
            $privileges[$code] = $privilege;
        }

        return $privileges;
    }

    private function createRoles(ObjectManager $manager, array $privileges): array
    {
        $roles = [];

        // ROLE_ADMIN - Full access to everything
        $adminRole = new Role();
        $adminRole->setName('ROLE_ADMIN');
        foreach ($privileges as $privilege) {
            $adminRole->addPrivilege($privilege);
        }
        $manager->persist($adminRole);
        $roles['ROLE_ADMIN'] = $adminRole;

        // ROLE_COMMERCIAL - Sales workflow management
        $commercialRole = new Role();
        $commercialRole->setName('ROLE_COMMERCIAL');
        $commercialPrivileges = [
            'CLIENT_MANAGE', 'CLIENT_VIEW',
            'ARTICLE_MANAGE', 'ARTICLE_VIEW',
            'ORDER_CREATE', 'ORDER_EDIT', 'ORDER_CANCEL', 'ORDER_VIEW',
            'DELIVERY_CREATE', 'DELIVERY_CANCEL', 'DELIVERY_VIEW',
            'INVOICE_CREATE', 'INVOICE_CANCEL', 'INVOICE_VIEW',
            'PAYMENT_CREATE', 'PAYMENT_CANCEL', 'PAYMENT_VIEW',
        ];
        foreach ($commercialPrivileges as $code) {
            if (isset($privileges[$code])) {
                $commercialRole->addPrivilege($privileges[$code]);
            }
        }
        $manager->persist($commercialRole);
        $roles['ROLE_COMMERCIAL'] = $commercialRole;

        // ROLE_COMPTABLE - Read-only access
        $comptableRole = new Role();
        $comptableRole->setName('ROLE_COMPTABLE');
        $comptablePrivileges = [
            'CLIENT_VIEW',
            'ARTICLE_VIEW',
            'ORDER_VIEW',
            'DELIVERY_VIEW',
            'INVOICE_VIEW',
            'PAYMENT_VIEW',
            'AUDIT_VIEW',
        ];
        foreach ($comptablePrivileges as $code) {
            if (isset($privileges[$code])) {
                $comptableRole->addPrivilege($privileges[$code]);
            }
        }
        $manager->persist($comptableRole);
        $roles['ROLE_COMPTABLE'] = $comptableRole;

        return $roles;
    }

    private function createUsers(ObjectManager $manager, array $roles): void
    {
        // Admin user
        $admin = new User();
        $admin->setName('Administrateur');
        $admin->setEmail('admin@bbc.local');
        $admin->setPasswordHash(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $admin->setActive(true);
        $admin->setRole($roles['ROLE_ADMIN']);
        $manager->persist($admin);

        // Commercial user (example)
        $commercial = new User();
        $commercial->setName('Jean Dupont');
        $commercial->setEmail('commercial@bbc.local');
        $commercial->setPasswordHash(
            $this->passwordHasher->hashPassword($commercial, 'commercial123')
        );
        $commercial->setActive(true);
        $commercial->setRole($roles['ROLE_COMMERCIAL']);
        $manager->persist($commercial);

        // Comptable user (example)
        $comptable = new User();
        $comptable->setName('Marie Martin');
        $comptable->setEmail('comptable@bbc.local');
        $comptable->setPasswordHash(
            $this->passwordHasher->hashPassword($comptable, 'comptable123')
        );
        $comptable->setActive(true);
        $comptable->setRole($roles['ROLE_COMPTABLE']);
        $manager->persist($comptable);
    }

    private function createClients(ObjectManager $manager): void
    {
        $clientsData = [
            [
                'code' => 'CLI001',
                'matricule' => '1234567/A/M/000',
                'name' => 'BTP Solutions Tunisie',
                'address' => '123 Avenue Habib Bourguiba, Tunis 1000',
            ],
            [
                'code' => 'CLI002',
                'matricule' => '2345678/B/M/000',
                'name' => 'Électronique Plus',
                'address' => '456 Rue Mohamed Ali, Sfax 3000',
            ],
            [
                'code' => 'CLI003',
                'matricule' => '3456789/C/M/000',
                'name' => 'Fournitures Bureau SARL',
                'address' => '789 Avenue de France, Sousse 4000',
            ],
            [
                'code' => 'CLI004',
                'matricule' => '4567890/D/M/000',
                'name' => 'Commerce Textile Import/Export',
                'address' => '321 Boulevard de la Gare, Tunis 1000',
            ],
            [
                'code' => 'CLI005',
                'matricule' => '5678901/E/M/000',
                'name' => 'Agence Voyage Horizons',
                'address' => '654 Rue du Parc, Hammamet 8050',
            ],
            [
                'code' => 'CLI006',
                'matricule' => '6789012/F/M/000',
                'name' => 'Pharmacie Ben Salah',
                'address' => '987 Avenue Farhat Hached, Kairouan 3100',
            ],
            [
                'code' => 'CLI007',
                'matricule' => '7890123/G/M/000',
                'name' => 'Restaurant La Méditerranée',
                'address' => '147 Cours Taieb Mhiri, Sfax 3000',
            ],
            [
                'code' => 'CLI008',
                'matricule' => '8901234/H/M/000',
                'name' => 'Garage Auto Service Express',
                'address' => '258 Route de Gafsa, Gafsa 2100',
            ],
        ];

        foreach ($clientsData as $data) {
            $client = new Client();
            $client->setClientCode($data['code']);
            $client->setMatriculeFiscale($data['matricule']);
            $client->setName($data['name']);
            $client->setAddress($data['address']);
            $client->setActive(true);
            $manager->persist($client);
        }
    }

    private function createArticles(ObjectManager $manager): void
    {
        $articlesData = [
            [
                'code' => 'ART001',
                'designation' => 'Ciment Portland Blanc 50kg',
            ],
            [
                'code' => 'ART002',
                'designation' => 'Carrelage Céramique 30x30 Blanc',
            ],
            [
                'code' => 'ART003',
                'designation' => 'Peinture Acrylique Premium 20L Blanc Pur',
            ],
            [
                'code' => 'ART004',
                'designation' => 'Tuyauterie PVC 112mm par 3m',
            ],
            [
                'code' => 'ART005',
                'designation' => 'Fil de cuivre électrique 2.5mm²',
            ],
            [
                'code' => 'ART006',
                'designation' => 'Disjoncteur différentiel 16A/30mA',
            ],
            [
                'code' => 'ART007',
                'designation' => 'Plaque plâtre standard 2.5x1.2m',
            ],
            [
                'code' => 'ART008',
                'designation' => 'Porte intérieure chêne clair 80cm',
            ],
            [
                'code' => 'ART009',
                'designation' => 'Fenêtre aluminium 120x100 double vitrage',
            ],
            [
                'code' => 'ART010',
                'designation' => 'Isolant laine minérale 100mm rouleau',
            ],
        ];

        foreach ($articlesData as $data) {
            $article = new Article();
            $article->setCode($data['code']);
            $article->setDesignation($data['designation']);
            $article->setActive(true);
            $manager->persist($article);
        }
    }
}
