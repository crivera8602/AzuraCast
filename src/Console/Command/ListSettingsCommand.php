<?php
namespace App\Console\Command;

use App\Entity;
use App\Utilities;
use Azura\Console\Command\CommandAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListSettingsCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Entity\Repository\SettingsRepository $settings_repo
    ) {
        $io->title(__('AzuraCast Settings'));

        $headers = [
            __('Setting Key'),
            __('Setting Value'),
        ];
        $rows = [];

        $all_settings = $settings_repo->fetchAll();
        foreach ($all_settings as $setting_key => $setting_value) {
            $value = print_r($setting_value, true);
            $value = Utilities::truncateText($value, 600);

            $rows[] = [$setting_key, $value];
        }

        $io->table($headers, $rows);
    }
}
