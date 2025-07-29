<?php

namespace App\Jobs;

use App\Enums\SubmitResult;
use App\Models\Problem;
use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UpdateProblemRatingJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $problemIds = [];
        Rating::where('computed', false)->select('problem_id')->distinct()->get()->each(function ($rating) {
            /** @var Problem */
            $rating->computed = true;
            $rating->save();
            $problemIds[] = $rating->problem_id;
        });
        $sumUsersResolvedPercentage = 0;
        $sumUsersTried = 0;
        $sumResolutionsPercentage = 0;
        $problemCount = Problem::count();
        if ($problemCount == 0) {
            return;
        }
        $problems = [];
        foreach (Problem::lazy() as $problem) {
            $usersWhoTried = $problem->submissions()->whereHas('user')->distinct('user_id')->where('user_id', '!=', $problem->user_id)->count()
                           + $problem->submissions()->join('competitor_submission', 'submissions.id', 'competitor_submission.submission_id')->distinct('competitor_id')->count();
            $usersWhoResolved = $problem->submissions()->whereHas('user')->distinct('user_id')->where('user_id', '!=', $problem->user_id)->where('result', SubmitResult::Accepted)->count();
            $totalTries = $problem->submissions()->where('user_id', '!=', $problem->user_id)->count();
            $totalResolutions = $problem->submissions()->where('result', SubmitResult::Accepted)->where('user_id', '!=', $problem->user_id)->count();
            $sumUsersResolvedPercentage += $usersWhoResolved / max(1, $usersWhoTried);
            $sumUsersTried += $usersWhoTried;
            $sumResolutionsPercentage += $totalResolutions / max(1, $totalTries);
            $problems[$problem->id] = [
                'usersWhoTried' => $usersWhoTried,
                'usersWhoResolved' => $usersWhoResolved,
                'totalTries' => $totalTries,
                'totalResolutions' => $totalResolutions,
            ];
        }
        $meanUserTried = $sumUsersTried / $problemCount;
        $meanUsersResolved = $sumUsersResolvedPercentage / $problemCount;
        $meanResolutions = $sumResolutionsPercentage / $problemCount;
        foreach (Problem::lazy() as $problem) {
            $sum = $problem->ratings()->sum('value');
            $count = $problem->ratings()->count();
            $data = $problems[$problem->id];
            $difficulty = $this->calcular_dificuldade(
                $data['usersWhoTried'],
                $data['usersWhoResolved'],
                $data['totalTries'],
                $data['totalResolutions'],
                $meanUsersResolved,
                $meanResolutions,
                $meanUserTried
            );
            dump($problem->title, $difficulty);
            dump([
                $data['usersWhoTried'],
                $data['usersWhoResolved'],
                $data['totalTries'],
                $data['totalResolutions'],
                $meanUsersResolved,
                $meanResolutions,
            ]);
            $problem->rating = (($sum) / ($count > 0 ? $count : 1) + $difficulty) / ($count > 0 ? 2 : 1);
            $problem->save();
        }
    }

    private function calcular_dificuldade(
        $n_total_pessoas,
        $n_pessoas_que_resolveram,
        $n_total_tentativas,
        $n_resolucoes,
        $media_pessoas_que_resolveram,
        $media_taxa_acerto_tentativas,
        $media_pessoas_que_tentou_algum_problema
    ) {
        if ($n_total_pessoas == 0 || $media_pessoas_que_resolveram == 0 || $media_taxa_acerto_tentativas == 0) {
            return 10.0;
        }

        // Parte 1: dificuldade baseada na comparação de resoluções
        $resolucoes_relativas = ($n_pessoas_que_resolveram / $n_total_pessoas) / max(1, $media_pessoas_que_resolveram);
        $dificuldade_resolucao = max(0.0, 1 - $resolucoes_relativas); // mais resoluções que a média = mais fácil

        // Parte 2: dificuldade baseada em taxa de acerto por tentativa
        $taxa_relativa = ($n_resolucoes / max(1, $n_total_tentativas)) / max(1, $media_taxa_acerto_tentativas);
        $dificuldade_tentativas = max(0.0, 1 - $taxa_relativa); // taxa de acerto maior = mais fácil

        // Parte 3: dificuldade baseada em taxa de tentativas
        $taxa_tentativas_relativa = $n_total_pessoas / max(1, $media_pessoas_que_tentou_algum_problema);
        $dificultade_tentativas_relativa = max(0.0, 1 - $taxa_tentativas_relativa); // mais tentativas que a média = mais difícil

        // Combinação ponderada (ajuste os pesos conforme desejar)
        $dificuldade_bruta = 0.35 * $dificuldade_resolucao + 0.20 * $dificuldade_tentativas + 0.45 * $dificultade_tentativas_relativa;

        // Escala final de 0 a 10
        return round($dificuldade_bruta * 10, 2);
    }
}
