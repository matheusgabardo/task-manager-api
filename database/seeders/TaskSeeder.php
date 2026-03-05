<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        $taskTemplates = [
            'Criar wireframes das páginas principais',
            'Implementar autenticação de utilizadores',
            'Configurar pipeline de CI/CD',
            'Escrever testes unitários',
            'Otimizar queries do banco de dados',
            'Desenvolver componente de navegação',
            'Implementar sistema de notificações',
            'Criar documentação da API',
            'Fazer revisão de código do módulo principal',
            'Configurar monitoramento de erros',
            'Desenvolver página de dashboard',
            'Implementar filtros de pesquisa',
            'Criar seeds para ambiente de desenvolvimento',
            'Otimizar carregamento de imagens',
            'Implementar paginação nos listings',
        ];

        $statuses = ['todo', 'in_progress', 'done'];
        $priorities = ['low', 'medium', 'high'];

        Project::all()->each(function (Project $project) use ($faker, $taskTemplates, $statuses, $priorities) {
            $count = $faker->numberBetween(8, 15);
            $selectedTasks = $faker->randomElements($taskTemplates, min($count, count($taskTemplates)));

            foreach ($selectedTasks as $title) {
                Task::create([
                    'project_id' => $project->id,
                    'title' => $title,
                    'description' => $faker->optional(0.7)->sentence(10),
                    'status' => $faker->randomElement($statuses),
                    'priority' => $faker->randomElement($priorities),
                    'due_date' => $faker->optional(0.6)->dateTimeBetween('-1 week', '+1 month')?->format('Y-m-d'),
                ]);
            }
        });
    }
}
