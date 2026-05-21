<?php

namespace App\Livewire\Admin;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use App\Services\ScoreCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    public function render()
    {
        // Anchor: students with an authoritative (final) exam — the three charts share this set.
        $authoritativeStudentIds = Exam::query()
            ->where('is_authoritative', true)
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->unique()
            ->values();

        return view('livewire.admin.dashboard', [
            'user'               => Auth::user(),
            'totalStudents'      => Student::notMerged()->count(),
            'totalUsers'         => User::where('is_super_admin', false)->count(),
            'totalExams'         => Exam::count(),
            'approvedExams'      => Exam::where('status', ExamStatus::Approved)->count(),

            'scoreDistribution'    => $this->scoreDistribution(),
            'genderDistribution'   => $this->genderDistribution($authoritativeStudentIds),
            'zoneDistribution'     => $this->zoneDistribution($authoritativeStudentIds),
            'examTypeDistribution' => $this->examTypeDistribution(),
            'passingScore'         => ScoreCalculator::passingScore(),
        ]);
    }

    private function examTypeDistribution(): array
    {
        $rows = Exam::query()
            ->where('is_authoritative', true)
            ->whereNotNull('exam_type')
            ->select('exam_type', DB::raw('count(*) as count'))
            ->groupBy('exam_type')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = $row->exam_type?->label() ?? '—';
            $counts[] = (int) $row->count;
        }
        return ['labels' => $labels, 'counts' => $counts];
    }

    private function scoreDistribution(): array
    {
        $bins   = array_fill(0, 10, 0);
        $labels = ['0-9', '10-19', '20-29', '30-39', '40-49', '50-59', '60-69', '70-79', '80-89', '90-100'];

        Exam::query()
            ->where('is_authoritative', true)
            ->whereNotNull('total_score')
            ->pluck('total_score')
            ->each(function ($score) use (&$bins) {
                $idx = min(9, (int) ((float) $score / 10));
                $bins[$idx]++;
            });

        return ['labels' => $labels, 'counts' => $bins];
    }

    private function genderDistribution($studentIds): array
    {
        $rows = Student::query()
            ->whereIn('id', $studentIds)
            ->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = $row->gender?->label() ?? 'غير محدد';
            $counts[] = (int) $row->count;
        }
        return ['labels' => $labels, 'counts' => $counts];
    }

    private function zoneDistribution($studentIds): array
    {
        $zoneLabels = [
            'East Gaza'  => 'شرق غزة',
            'West Gaza'  => 'غرب غزة',
            'North Gaza' => 'شمال غزة',
            'South Gaza' => 'جنوب غزة',
        ];

        $rows = Student::query()
            ->whereIn('id', $studentIds)
            ->select('student_zone', DB::raw('count(*) as count'))
            ->groupBy('student_zone')
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rows as $row) {
            $labels[] = $row->student_zone ? ($zoneLabels[$row->student_zone] ?? $row->student_zone) : 'غير محدد';
            $counts[] = (int) $row->count;
        }
        return ['labels' => $labels, 'counts' => $counts];
    }
}
