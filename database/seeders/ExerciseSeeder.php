<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exercises = [
            // Peito
            [
                'name' => 'Supino Reto com Barra',
                'muscle_group' => 'Peito',
                'description' => 'Deitado no banco reto, segure a barra com pegada ligeiramente mais larga que os ombros. Desça até o peito e empurre para cima.',
                'photo_url' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Supino Inclinado com Halteres',
                'muscle_group' => 'Peito',
                'description' => 'Banco inclinado a 30-45 graus. Empurre os halteres para cima, focando na parte superior do peitoral.',
                'photo_url' => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Crucifixo Reto',
                'muscle_group' => 'Peito',
                'description' => 'Deitado, abra os braços com leve flexão nos cotovelos, alongando o peitoral, e feche no topo.',
                'photo_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Crossover (Polia Alta)',
                'muscle_group' => 'Peito',
                'description' => 'Em pé, puxe as polias de cima para baixo e para o centro, contraindo o peitoral.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Flexão de Braços (Push-up)',
                'muscle_group' => 'Peito',
                'description' => 'Mãos apoiadas no chão, corpo reto. Desça o peito até próximo ao chão e suba.',
                'photo_url' => 'https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?q=80&w=2069&auto=format&fit=crop'
            ],
            [
                'name' => 'Supino Declinado',
                'muscle_group' => 'Peito',
                'description' => 'Banco declinado. Foco na parte inferior do peitoral.',
                'photo_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Peck Deck (Voador)',
                'muscle_group' => 'Peito',
                'description' => 'Sentado na máquina, feche os braços à frente do corpo, isolando o peitoral.',
                'photo_url' => 'https://images.unsplash.com/photo-1598532163257-ec79d8465ce8?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Pull-over com Halter',
                'muscle_group' => 'Peito',
                'description' => 'Deitado, segure um halter com as duas mãos e leve-o atrás da cabeça, alongando o tronco.',
                'photo_url' => 'https://plus.unsplash.com/premium_photo-1664109999537-088e7d964da2?q=80&w=2071&auto=format&fit=crop'
            ],

            // Costas
            [
                'name' => 'Puxada Alta (Lat Pulldown)',
                'muscle_group' => 'Costas',
                'description' => 'Sentado, puxa a barra em direção ao peito superior, ativando as dorsais.',
                'photo_url' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?q=80&w=2074&auto=format&fit=crop'
            ],
            [
                'name' => 'Remada Curvada com Barra',
                'muscle_group' => 'Costas',
                'description' => 'Tronco inclinado à frente, puxe a barra em direção ao abdômen.',
                'photo_url' => 'https://images.unsplash.com/photo-1603287681836-e6c33e21019e?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Remada Baixa com Triângulo',
                'muscle_group' => 'Costas',
                'description' => 'Sentado na polia baixa, puxe o triângulo em direção ao abdômen, mantendo a coluna reta.',
                'photo_url' => 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?q=80&w=1974&auto=format&fit=crop'
            ],
            [
                'name' => 'Remada Unilateral (Serrote)',
                'muscle_group' => 'Costas',
                'description' => 'Apoiado no banco, puxe o halter unilateralmente em direção ao quadril.',
                'photo_url' => 'https://images.unsplash.com/photo-1532029837206-abbe2b7a4bdd?q=80&w=1974&auto=format&fit=crop'
            ],
            [
                'name' => 'Barra Fixa (Pronada)',
                'muscle_group' => 'Costas',
                'description' => 'Pendurado na barra, puxe o corpo para cima até o queixo passar da barra.',
                'photo_url' => 'https://images.unsplash.com/photo-1598971639058-211a7219488b?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Levantamento Terra (Deadlift)',
                'muscle_group' => 'Costas',
                'description' => 'Levante a barra do chão estendendo o quadril e joelhos. Exercício completo para a cadeia posterior.',
                'photo_url' => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=2069&auto=format&fit=crop'
            ],
            [
                'name' => 'Pull-down com Corda',
                'muscle_group' => 'Costas',
                'description' => 'Em pé na polia alta, braços estendidos, leve a corda até as coxas.',
                'photo_url' => 'https://images.unsplash.com/photo-1590227632626-6218d36eb788?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Remada Cavalinho (Barra T)',
                'muscle_group' => 'Costas',
                'description' => 'Tronco inclinado, puxe a barra T em direção ao peito/abdômen.',
                'photo_url' => 'https://plus.unsplash.com/premium_photo-1663100722417-6e36673fe0ed?q=80&w=2070&auto=format&fit=crop'
            ],

            // Pernas
            [
                'name' => 'Agachamento Livre com Barra',
                'muscle_group' => 'Pernas',
                'description' => 'Barra nas costas, flexione joelhos e quadril descendo como se fosse sentar.',
                'photo_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Leg Press 45°',
                'muscle_group' => 'Pernas',
                'description' => 'Empurre a plataforma com os pés, estendendo as pernas.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483262268-9c96d8a31892?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Cadeira Extensora',
                'muscle_group' => 'Pernas',
                'description' => 'Sentado, estenda os joelhos levantando o peso. Foco em quadríceps.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483297929-37f416fec7cd?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Mesa Flexora',
                'muscle_group' => 'Pernas',
                'description' => 'Deitado de bruços, flexione os joelhos trazendo o calcanhar ao glúteo.',
                'photo_url' => 'https://images.unsplash.com/photo-1596350553841-275798579978?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Afundo ou Passada (Lunge)',
                'muscle_group' => 'Pernas',
                'description' => 'Dê um passo à frente e agache até o joelho de trás quase tocar o chão.',
                'photo_url' => 'https://images.unsplash.com/photo-1574680178050-55c6a6a96e0a?q=80&w=2069&auto=format&fit=crop'
            ],
            [
                'name' => 'Stiff com Barra ou Halteres',
                'muscle_group' => 'Pernas',
                'description' => 'Pernas semi-estendidas, desça o tronco mantendo a coluna reta. Foco em posteriores.',
                'photo_url' => 'https://images.unsplash.com/photo-1584735935682-2fddb6ca980b?q=80&w=1974&auto=format&fit=crop'
            ],
            [
                'name' => 'Elevação Pélvica',
                'muscle_group' => 'Pernas',
                'description' => 'Costas apoiadas no banco, eleve o quadril contraindo os glúteos.',
                'photo_url' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?q=80&w=2074&auto=format&fit=crop'
            ],
            [
                'name' => 'Cadeira Abdutora',
                'muscle_group' => 'Pernas',
                'description' => 'Sentado, afaste as pernas contra a resistência.',
                'photo_url' => 'https://images.unsplash.com/photo-1632168532981-d249f6eb2546?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Agachamento Sumô',
                'muscle_group' => 'Pernas',
                'description' => 'Pés afastados além da largura dos, pontas para fora. Agache mantendo postura.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009137042-c552e485697a?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Hack Machine',
                'muscle_group' => 'Pernas',
                'description' => 'Agachamento guiado na máquina Hack, apoiando as costas.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483262024-857e492215c0?q=80&w=2070&auto=format&fit=crop'
            ],

            // Ombros
            [
                'name' => 'Desenvolvimento com Halteres',
                'muscle_group' => 'Ombros',
                'description' => 'Sentado ou em pé, empurre os halteres para cima da cabeça.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009137042-c552e485697a?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Elevação Lateral',
                'muscle_group' => 'Ombros',
                'description' => 'Levante os braços lateralmente até a altura dos ombros.',
                'photo_url' => 'https://images.unsplash.com/photo-1517963879466-eeb23687d910?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Elevação Frontal',
                'muscle_group' => 'Ombros',
                'description' => 'Levante o peso à frente do corpo até a altura dos ombros.',
                'photo_url' => 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?q=80&w=2069&auto=format&fit=crop'
            ],
            [
                'name' => 'Crucifixo Inverso',
                'muscle_group' => 'Ombros',
                'description' => 'Tronco inclinado ou na máquina, abra os braços para trás. Foco em posterior de ombro.',
                'photo_url' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Remada Alta',
                'muscle_group' => 'Ombros',
                'description' => 'Puxe a barra verticalmente rente ao corpo até a altura do peito.',
                'photo_url' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?q=80&w=2074&auto=format&fit=crop'
            ],
            [
                'name' => 'Encolhimento de Ombros',
                'muscle_group' => 'Ombros',
                'description' => 'Segure os pesos e encolha os ombros em direção às orelhas. Trapézio.',
                'photo_url' => 'https://images.unsplash.com/photo-1532384748853-8f54a8f476e2?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Arnold Press',
                'muscle_group' => 'Ombros',
                'description' => 'Desenvolvimento com rotação dos punhos durante o movimento.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483298606-407519ba579b?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Face Pull (Corda no Rosto)',
                'muscle_group' => 'Ombros',
                'description' => 'Puxe a corda em direção ao rosto, abrindo os cotovelos.',
                'photo_url' => 'https://images.unsplash.com/photo-1590227632906-81cf9df3ee85?q=80&w=2070&auto=format&fit=crop'
            ],

            // Braços
            [
                'name' => 'Rosca Direta (Barra W)',
                'muscle_group' => 'Bíceps',
                'description' => 'Em pé, flexione os cotovelos levantando a barra até o peito.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Rosca Martelo',
                'muscle_group' => 'Bíceps',
                'description' => 'Pegada neutra (palmas para dentro), flexione os cotovelos.',
                'photo_url' => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Rosca Concentrada',
                'muscle_group' => 'Bíceps',
                'description' => 'Sentado, apoie o cotovelo na coxa interna e flexione o braço.',
                'photo_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Rosca Scott',
                'muscle_group' => 'Bíceps',
                'description' => 'Braços apoiados no banco Scott, flexione isolando o bíceps.',
                'photo_url' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?q=80&w=2074&auto=format&fit=crop'
            ],
            [
                'name' => 'Tríceps Pulley',
                'muscle_group' => 'Tríceps',
                'description' => 'Na polia alta, estenda os cotovelos empurrando para baixo.',
                'photo_url' => 'https://images.unsplash.com/photo-1590227632626-6218d36eb788?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Tríceps Testa',
                'muscle_group' => 'Tríceps',
                'description' => 'Deitado, baixe a barra/halteres até a testa flexionando cotovelos e estenda.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Tríceps Francês',
                'muscle_group' => 'Tríceps',
                'description' => 'Braços acima da cabeça, flexione cotovelos levando o peso atrás da nuca.',
                'photo_url' => 'https://images.unsplash.com/photo-1598532163257-ec79d8465ce8?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Mergulho no Banco (Dips)',
                'muscle_group' => 'Tríceps',
                'description' => 'Apoiado no banco, desça o corpo flexionando os braços.',
                'photo_url' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Rosca Inversa',
                'muscle_group' => 'Antebraço',
                'description' => 'Pegada pronada (palmas para baixo), faça a rosca. Foco em antebraço.',
                'photo_url' => 'https://images.unsplash.com/photo-1590227632626-6218d36eb788?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Flexão de Punho',
                'muscle_group' => 'Antebraço',
                'description' => 'Antebraço apoiado, movimente apenas o punho para cima e para baixo.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483298606-407519ba579b?q=80&w=2070&auto=format&fit=crop'
            ],

            // Panturrilhas
            [
                'name' => 'Gêmeos em Pé',
                'muscle_group' => 'Pernas', // Ou Panturrilha
                'description' => 'Eleve os calcanhares ficando na ponta dos pés.',
                'photo_url' => 'https://images.unsplash.com/photo-1581009137042-c552e485697a?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Gêmeos Sentado',
                'muscle_group' => 'Pernas', // Ou Panturrilha
                'description' => 'Sentado com peso nos joelhos, eleve os calcanhares. Foco no sóleo.',
                'photo_url' => 'https://images.unsplash.com/photo-1627483262024-857e492215c0?q=80&w=2070&auto=format&fit=crop'
            ],

            // Core
            [
                'name' => 'Abdominal Crunch',
                'muscle_group' => 'Abdômen',
                'description' => 'Deitado, flexione o tronco tentando aproximar as costelas do quadril.',
                'photo_url' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Prancha Isométrica',
                'muscle_group' => 'Abdômen',
                'description' => 'Sustente o corpo apoiado nos antebraços e ponta dos pés, contraindo o abdômen.',
                'photo_url' => 'https://images.unsplash.com/photo-1566241440091-ec10de8db2e1?q=80&w=2016&auto=format&fit=crop'
            ],
            [
                'name' => 'Abdominal Infra',
                'muscle_group' => 'Abdômen',
                'description' => 'Deitado, eleve as pernas mantendo-as estendidas ou semi-flexionadas.',
                'photo_url' => 'https://images.unsplash.com/photo-1544465544-1b71aee9dfa3?q=80&w=2070&auto=format&fit=crop'
            ],
            [
                'name' => 'Extensão Lombar',
                'muscle_group' => 'Costas', // Lombar
                'description' => 'No banco romano ou chão, estenda o tronco ativando a lombar.',
                'photo_url' => 'https://images.unsplash.com/photo-1584735935682-2fddb6ca980b?q=80&w=1974&auto=format&fit=crop'
            ],
        ];

        foreach ($exercises as $exercise) {
            Exercise::firstOrCreate(
                ['name' => $exercise['name']],
                [
                    'muscle_group' => $exercise['muscle_group'],
                    'description' => $exercise['description'],
                    'photo_url' => $exercise['photo_url'],
                    'user_id' => null,
                ]
            );
        }
    }
}
