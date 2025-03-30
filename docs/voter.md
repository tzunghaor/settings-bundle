Using security voters
=====================

You can create security voter(s) if you need finer control over who might edit which settings.

Make sure that security-core component is installed:

```sh
$ composer require symfony/security-core
```

Turn on security for the bundle:

```yaml
# config/packages/tzunghaor_settings.yaml

tzunghaor_settings:
  security: true
  collections:
    # ...
```

Create your custom voter
------------------------

Create a [security voter](https://symfony.com/doc/current/security/voters.html) 
that supports **Tzunghaor\SettingsBundle\Model\SettingSectionAddress** as subject
and "edit" as attribute.

Any or all of the three fields of a SettingSectionAddress might be null, then the
question is: "Can we fill the nulls in a way that the user has right to edit?"

If you are using your own [scope provider](scopes.md), then make sure that the
scope provider's getScopeDisplayHierarchy() method takes the security voter's logic in 
account. (Optimally it should return only scopes that your voter approves.)

```php
// src/Security/SettingsVoter.php
namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Tzunghaor\SettingsBundle\Model\SettingSectionAddress;

class SettingsVoter extends Voter
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof SettingSectionAddress && $attribute === 'edit';
    }


    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
        
        if ($user === null) {
            // not authenticated users are not allowed to edit any settings
            return false;
        }
        
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        // This example doesn't check $subject->getSectionName(), but you can write more fine-grained
        // logic using that too. 
        
        /** @var SettingSectionAddress $subject */
       
        // Our 'season' collection contains system-wide settings, so only admins are allowed to edit them.
        if ($subject->getCollectionName() === 'season') {
            return $isAdmin;
        }

        // Admins are allowed to edit any user's settings, users are allowed to edit their own settings.
        if ($subject->getCollectionName() === 'user') {
            return $isAdmin || ($subject->getScope() === $user->getUserIdentifier());
        }
        
        // Deny by default 
        return false;
    }
}
```

Use the voter in your code
--------------------------

If you want to do the same authorization check that the bundle does 
(e.g. to show an edit settings link on your page only if the authenticated user can edit those settings),
you can use the isGranted() method in Symfony's security component:

```php
// src/Controller/MyController.php
namespace App\Controller;

use App\Entity\User;
use App\UserSettings\BasicSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tzunghaor\SettingsBundle\Service\SettingsService;

class IndexController extends AbstractController
{
    // ...
    
    #[Route("/is-granted/{user}")]
    public function granted(SettingsService $userSettingsService, Security $security, User $user): Response
    {
        // To get the address of the section returned by SettingsService::getSection() ... 
        $settings = $userSettingsService->getSection(BasicSettings::class, $user);
        // Call SettingsService::getSectionAddress() with the same arguments
        $settingAddress = $userSettingsService->getSectionAddress(BasicSettings::class, $user);
        $canString = $security->isGranted('edit', $settingAddress) ? 'CAN' : 'can NOT';

        return new Response(
            'Authenticated user ' . $canString . ' edit settings of user ' . $user->getUserIdentifier()
        );
    }    
}
```