<?php

namespace Database\Seeders;

use App\Enums\TalentShowStatus;
use App\Models\Judge;
use App\Models\TalentShow;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TalentShowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command?->warn('Δεν βρέθηκε admin user. Τρέξτε πρώτα το AdminUserSeeder.');

            return;
        }

        $show = TalentShow::updateOrCreate(
            ['slug' => 'demo-talent-show-2026'],
            [
                'title' => 'Demo Talent Show 2026',
                'description' => 'Επίδειξη ζωντανής βαθμολόγησης',
                'venue' => 'Κεντρική Σκηνή',
                'event_date' => now()->addDays(7)->toDateString(),
                'status' => TalentShowStatus::Draft,
                'created_by' => $admin->id,
            ]
        );

        $teamNames = [
            'Ομάδα Alpha', 'Ομάδα Beta', 'Ομάδα Gamma', 'Ομάδα Delta',
            'Ομάδα Epsilon', 'Ομάδα Zeta', 'Ομάδα Eta', 'Ομάδα Theta',
        ];

        foreach ($teamNames as $index => $name) {
            Team::updateOrCreate(
                ['talent_show_id' => $show->id, 'name' => $name],
                [
                    'code' => 'T'.($index + 1),
                    'display_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $judges = [
            ['name' => 'Μάνος Κοκολάκης', 'title' => 'Τραγουδιστής', 'is_final_voter' => false],
            ['name' => 'Γιώργος Στρατάκης', 'title' => 'Κρητικός τραγουδιστής', 'is_final_voter' => false],
            ['name' => 'Χρήστος Στρατάκης', 'title' => 'Ηθοποιός', 'is_final_voter' => false],
            ['name' => 'Αριστέα Καλογρίδη', 'title' => 'Παρουσιάστρια', 'is_final_voter' => false],
            ['name' => 'Γεωργία Καρφή', 'title' => 'Προπονήτρια ενόργανης', 'is_final_voter' => false],
            ['name' => 'Ήρω Γεωργακάκη', 'title' => 'Τραγουδίστρια', 'is_final_voter' => true],
        ];

        $keepNames = [];

        foreach ($judges as $index => $judge) {
            Judge::updateOrCreate(
                ['talent_show_id' => $show->id, 'name' => $judge['name']],
                [
                    'title' => $judge['title'],
                    'display_order' => $index + 1,
                    'is_active' => true,
                    'is_final_voter' => $judge['is_final_voter'],
                ]
            );

            $keepNames[] = $judge['name'];
        }

        Judge::where('talent_show_id', $show->id)
            ->whereNotIn('name', $keepNames)
            ->delete();
    }
}