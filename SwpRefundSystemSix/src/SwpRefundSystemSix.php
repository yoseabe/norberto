<?php declare(strict_types=1);

namespace Swp\RefundSystemSix;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Swp\RefundSystemSix\Utils\CustomFieldInstaller;
use Swp\RefundSystemSix\Utils\InstallUninstall;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SwpRefundSystemSix extends Plugin
{

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    /**
     * @param UpdateContext $updateContext
     * @return void
     */
    public function postUpdate(UpdateContext $updateContext): void
    {
        parent::postUpdate($updateContext);

        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        (new CustomFieldInstaller($this->container))->activate($updateContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        (new CustomFieldInstaller($this->container))->activate($activateContext->getContext());

        parent::activate($activateContext);

        /*
        $connection = $this->container->get(Connection::class);

        $value = "INSERT INTO refund_system (id, active, created_at) VALUES
                    ('".('Einweg')."', 1, '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Mehrweg')."', 1, '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Pfandfrei')."', 1, '".(date('Y-m-d H:i.s').'.000')."')
                    ON DUPLICATE KEY UPDATE active = active";
        $connection->executeQuery($value);

        $value = "INSERT INTO refund_system_translation (refund_system_id, language_id, `name`, description, created_at) VALUES
                    ('".('Einweg')."', (SELECT id FROM language WHERE name = 'Deutsch'), 'EINWEGPFAND',  'Einwegpfand', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Mehrweg')."', (SELECT id FROM language WHERE name = 'Deutsch'), 'MEHRWEGPFAND', 'Mehrwegpfand', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Pfandfrei')."', (SELECT id FROM language WHERE name = 'Deutsch'), 'PFANDFREI', 'Pfandfrei', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Einweg')."', (SELECT id FROM language WHERE name = 'English'), 'ONE-WAY',  'One-way-Refund', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Mehrweg')."', (SELECT id FROM language WHERE name = 'English'), 'REUSABLE' , 'Reusable-Refund', '".(date('Y-m-d H:i.s').'.000')."'),
                    ('".('Pfandfrei')."', (SELECT id FROM language WHERE name = 'English'), 'DEPOSIT FREE', 'Deposit free', '".(date('Y-m-d H:i.s').'.000')."')
                    ON DUPLICATE KEY UPDATE created_at = created_at";
        $connection->executeQuery($value);

        */
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        (new CustomFieldInstaller($this->container))->deactivate($deactivateContext->getContext());

        parent::deactivate($deactivateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        \assert($this->container instanceof ContainerInterface, 'Container is not set yet, please call setContainer() before calling boot(), see `platform/Core/Kernel.php:186`.');

        (new InstallUninstall($this->container))->uninstall($uninstallContext->getContext());
        (new CustomFieldInstaller($this->container))->uninstall($uninstallContext->getContext());
    }
}
