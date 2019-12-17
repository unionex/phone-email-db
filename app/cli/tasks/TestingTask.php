<?php
namespace App\Cli\Tasks;

use App\Models\StudentExamination;
use App\Models\StudyGroup;
use App\Models\StudyGroupExamination;
use App\Models\TestExamination;

class TestingTask extends \App\Cli\Task
{
    public function studentExaminationAction()
    {
        /** @var StudyGroupExamination[] $studyGroups */
        $studyGroups = StudyGroupExamination::find();

        foreach ($studyGroups as $studyGroup) {
            if (rand(1, 100) <= 10) {
                continue;
            }

            $test = $studyGroup->testExamination;
            $questions = $test->questions;

            foreach ($studyGroup->studentExaminations as $studentExamination) {
                foreach ($questions as $question) {
                    if (rand(1, 100) > 50) {
                        $studentExamination->setAnswer($question, $question->correctAnswer);
                    }
                }

                $studentExamination->save();

                if (rand(1, 100) > 40) {
                    $studentExamination->student->finishTest($studyGroup);
                }
            }

            if (rand(1, 100) > 70) {
                $studyGroup->finish();
            }
        }
    }

    /**
     * @throws \Phalcon\Exception
     */
    public function studyGroupExaminationAction()
    {
        $count = 500;

        /** @var TestExamination[] $tests */
        $tests = TestExamination::find();

        /** @var StudyGroup[] $studyGroups */
        $studyGroups = StudyGroup::find();

        for ($i = 0; $i < $count; $i++) {
            $studyGroup = $studyGroups[rand(0, count($studyGroups) - 1)];

            $teachers = $studyGroup->getTeachers();
            $students = $studyGroup->students;

            if (!$teachers || !$students) {
                continue;
            }

            $teacher = $teachers[rand(0, count($teachers) - 1)];

            $examination = new StudyGroupExamination();
            $examination->studyGroupId = $studyGroup->id;
            $examination->teacherId = $teacher->id;
            $examination->testExaminationId = $tests[rand(0, count($tests) - 1)]->id;
            $examination->startAt = new \DateTime("now");
            $examination->save();

            foreach ($students as $student) {
                if (rand(1, 100) > 40) {
                    $studentExamination = new StudentExamination();
                    $studentExamination->studyGroupExaminationId = $examination->id;
                    $studentExamination->studentId = $student->id;
                    $studentExamination->save();

                    if (rand(1, 100) > 70) {
                        $student->startTest($examination);
                    }
                }
            }
        }
    }
}