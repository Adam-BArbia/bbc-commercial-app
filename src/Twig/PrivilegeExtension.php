<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to expose privilege checking and user privilege info
 * 
 * Usage in templates:
 *   {% if has_privilege('CLIENT_MANAGE') %}
 *   {{ user_privileges()|length }}
 */
class PrivilegeExtension extends AbstractExtension
{
    public function __construct(private Security $security)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_privilege', [$this, 'hasPrivilege']),
            new TwigFunction('user_privileges', [$this, 'getUserPrivileges']),
        ];
    }

    /**
     * Check if current user has a specific privilege
     */
    public function hasPrivilege(string $privilegeCode): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        // Admins have all privileges
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $role = $user->getRole();
        if ($role === null) {
            return false;
        }

        return $role->hasPrivilege($privilegeCode);
    }

    /**
     * Get all privileges for the current user
     * 
     * @return array<string> Array of privilege codes
     */
    public function getUserPrivileges(): array
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return [];
        }

        $role = $user->getRole();
        if ($role === null) {
            return [];
        }

        return $role->getPrivileges()->map(fn($p) => $p->getCode())->toArray();
    }
}
