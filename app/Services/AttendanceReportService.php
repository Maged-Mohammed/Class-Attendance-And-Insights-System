<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassModel;

class AttendanceReportService
{
    /**
     * @param $class_id
     * @param $user
     * Method to validate and ensure the teacher is allowed to access the class
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateTeacherAccess ($class_id, $user)
    {
        $class = ClassModel::find ($class_id);
        if ( !$class) {
            return response ()->json (['message' => 'Class not found'], 404);
        }
        
        if ($user->role === 'teacher' && $user->id !== $class->teacher_id) {
            return response ()->json (['message' => 'You can only view data for your own class.'], 403);
        }
        
        return $class;
    }
    
    /**
     * @param $class_id
     * @param $start_date
     * @param $end_date
     * Method to fetch attendance data for a class and date range
     * @return mixed
     */
    public function getAttendanceData ($class_id, $start_date, $end_date)
    {
        return Attendance::where ('class_id', $class_id)
                         ->whereBetween ('date', [$start_date, $end_date])
                         ->selectRaw ('date, status, COUNT(*) as count')
                         ->groupBy ('date', 'status')
                         ->orderBy ('date')
                         ->get ();
    }
    
    /**
     * @param $attendanceTrends
     * Method to calculate attendance percentage for each day
     * @return mixed
     */
    public function calculateDailyAttendanceTrends ($attendanceTrends)
    {
        return $attendanceTrends->groupBy ('date')->map (function ($items) {
            $total = $items->sum ('count');
            return $items->map (function ($item) use ($total) {
                return [
                    'status'     => $item->status,
                    'count'      => $item->count,
                    'percentage' => $total > 0 ? round (($item->count / $total) * 100, 2) : 0,
                ];
            });
        });
    }
    
    /**
     * @param $class_id
     * @param $start_date
     * @param $end_date
     * Method to fetch the student with the highest absence rate
     * @return mixed
     */
    public function getStudentWithHighestAbsenceRate ($class_id, $start_date, $end_date)
    {
        $absenceRateQuery = Attendance::where ('class_id', $class_id)
                                      ->whereBetween ('date', [$start_date, $end_date])
                                      ->where ('status', 'absent')
                                      ->select ('student_id', \DB::raw ('COUNT(*) as absence_count'))
                                      ->groupBy ('student_id')
                                      ->orderByDesc ('absence_count')
                                      ->limit (1);
        
        return $absenceRateQuery->first ();
    }
}