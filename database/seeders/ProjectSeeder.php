<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            ['name' => 'Website Redesign', 'description' => 'Redesign completo do site institucional com foco em UX e performance.'],
            ['name' => 'Mobile App v2', 'description' => 'Segunda versão do aplicativo mobile com novas funcionalidades.'],
            ['name' => 'API Integration', 'description' => 'Integração com APIs de parceiros para sincronização de dados.'],
            ['name' => 'Data Migration', 'description' => 'Migração de dados do sistema legado para a nova plataforma.', 'status' => 'archived'],
            ['name' => 'Design System', 'description' => 'Criação de um design system unificado para todos os produtos.'],
        ];

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}
