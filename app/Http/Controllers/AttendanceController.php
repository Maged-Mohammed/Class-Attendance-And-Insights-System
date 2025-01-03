<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Services\AttendanceReportService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store (Request $request)
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'class_id'   => 'required|exists:classes,id',
            'status'     => 'required|in:present,absent,late',
        ];
        
        $validator = validator ()->make ($request->all (), $rules);
        
        if ($validator->fails ()) {
            return response ()->json (['message' => $validator->errors ()->first ()], 400);
        }
        
        $validatedData = $validator->validated ();
        
        $validatedData['date'] = now ()->toDateString ();
        
        // Role-based access control
        $user  = auth ()->user ();
        $class = ClassModel::find ($validatedData['class_id']);
        
        if ( !$class) {
            return response ()->json (['message' => 'Class not found'], 404);
        }
        
        // Ensure the teacher is marking attendance for their own class
        if ($user->role === 'teacher' && $user->id !== $class->teacher_id) {
            return response ()->json (['message' => 'You can only mark attendance for your own class.'], 403);
        }
        
        // Check if attendance has already been recorded for the student on the same day
        $existingAttendance = Attendance::where ('student_id', $validatedData['student_id'])
                                        ->where ('class_id', $validatedData['class_id'])
                                        ->where ('date', $validatedData['date'])
                                        ->first ();
        
        // If attendance already exists, return a message to prevent duplication
        if ($existingAttendance) {
            return response ()->json (['message' => 'Attendance already recorded for this student on this day.'], 400);
        }
        
        // If no duplicate is found, create the new attendance record
        Attendance::create ([
            'student_id' => $validatedData['student_id'],
            'class_id'   => $validatedData['class_id'],
            'status'     => $validatedData['status'],
            'date'       => $validatedData['date'],
        ]);
        
        // todo this a second way to
        // Prevent duplicate attendance entries for the same student on the same day
//        Attendance::updateOrCreate (
//            [
//                'student_id' => $validatedData['student_id'],
//                'class_id'   => $validatedData['class_id'],
//                'date'       => $validatedData['date'],
//            ],
//            ['status' => $validatedData['status']]
//        );
        
        return response ()->json (['message' => 'Attendance marked successfully'], 200);
    }
    
    /**
     * @param $class_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassAttendance ($class_id)
    {
        // Get the currently authenticated user
        $user = auth ()->user ();
        
        // Find the class by the provided class ID
        $class = ClassModel::find ($class_id);
        
        // If the class is not found, return a 404 error with a message
        if ( !$class) {
            return response ()->json (['message' => 'Class not found'], 404);
        }
        
        // Check if the teacher is trying to access attendance data for a class that is not theirs
        if ($user->role === 'teacher' && $user->id !== $class->teacher_id) {
            return response ()->json (['message' => 'You can only view attendance data for your own class.'], 403);
        }
        
        // Query the attendance data based on the user's role
        $attendanceQuery = Attendance::where ('class_id', $class_id)
                                     ->select ('status', \DB::raw ('COUNT(*) as count'))
                                     ->groupBy ('status');
        
        // Admins can access all attendance data, but teachers can only access their own class
        if ($user->role !== 'admin') {
            $attendanceQuery->where ('class_id', $class_id);
        }
        
        // Retrieve attendance data with eager loading of related data (if needed, e.g., for student details)
        $attendance = $attendanceQuery->with ('student')->get ();
        
        // If no attendance data exists, return an empty result
        if ($attendance->isEmpty ()) {
            return response ()->json (['attendance' => []], 200);
        }
        
        // Calculate the total number of attendance entries for the class
        $total = $attendance->sum ('count');
        
        // Prepare the response data, including attendance percentages
        $response = $attendance->map (function ($item) use ($total) {
            return [
                'status'     => $item->status,
                'count'      => $item->count,
                'percentage' => $total > 0 ? round (($item->count / $total) * 100, 2) : 0,
            ];
        });
        
        // Return the response with the attendance data
        return response ()->json (['attendance' => $response], 200);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReport (Request $request)
    {
        // Validate request parameters
        $request->validate ([
            'class_id'   => 'required|exists:classes,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $class_id   = $request->class_id;
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        
        // Get the authenticated user
        $user = auth ()->user ();
        
        // Initialize the service class
        $attendanceService = new AttendanceReportService();
        
        // Check teacher's access to the class
        $class = $attendanceService->validateTeacherAccess ($class_id, $user);

        if ($class instanceof \Illuminate\Http\JsonResponse) {
            return $class; // Return the error response
        }

        // Get attendance data for the class and date range
        $attendanceTrends = $attendanceService->getAttendanceData ($class_id, $start_date, $end_date);

        // Calculate the daily attendance trends
        $dailyTrends = $attendanceService->calculateDailyAttendanceTrends ($attendanceTrends);
        
        // Get the student with the highest absence rate
        $highestAbsenceStudent = $attendanceService->getStudentWithHighestAbsenceRate ($class_id, $start_date, $end_date);
        
        if ($highestAbsenceStudent) {
            $student            = Student::find ($highestAbsenceStudent->student_id);
            $highestAbsenceRate = [
                'student_name'  => $student ? $student->name : 'Unknown',
                'absence_count' => $highestAbsenceStudent->absence_count,
            ];
        }
        else {
            $highestAbsenceRate = NULL;
        }
        
        // Return the report data
        return response ()->json ([
            'daily_trends'         => $dailyTrends,
            'highest_absence_rate' => $highestAbsenceRate,
        ]);
    }
    
   
}
