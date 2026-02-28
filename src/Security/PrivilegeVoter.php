<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter to check application-level privileges (CLIENT_MANAGE, ORDER_CREATE, etc.)
 * 
 * Usage in controllers:
 *   $this->denyAccessUnlessGranted('CLIENT_MANAGE');
 * 
 * Usage in Twig:
 *   {% if is_granted('ORDER_CREATE') %}
 */
class PrivilegeVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        // This voter handles privilege codes (uppercase with underscores)
        // but not standard Symfony roles (ROLE_*)
        if (str_starts_with($attribute, 'ROLE_')) {
            return false;
        }
        
        // Accept any attribute that looks like a privilege code
        // (contains uppercase letters and underscores)
        return preg_match('/^[A-Z_]+$/', $attribute) === 1;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof User) {
            return false;
        }

        // Admins have all privileges
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Check if user's role has this privilege
        $role = $user->getRole();
        if ($role === null) {
            return false;
        }

        return $role->hasPrivilege($attribute);
    }
}
