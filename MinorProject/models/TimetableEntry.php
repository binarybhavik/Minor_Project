<?php
class TimetableEntry {
    private $id;
    private $semester;
    private $course;
    private $day;
    private $timeStart;
    private $timeEnd;
    private $subjectCode;
    private $subjectName;
    private $facultyName;
    private $roomNumber;
    
    public function __construct(
        $id, 
        $semester, 
        $course, 
        $day, 
        $timeStart, 
        $timeEnd, 
        $subjectCode, 
        $subjectName, 
        $facultyName, 
        $roomNumber
    ) {
        $this->id = $id;
        $this->semester = $semester;
        $this->course = $course;
        $this->day = $day;
        $this->timeStart = $timeStart;
        $this->timeEnd = $timeEnd;
        $this->subjectCode = $subjectCode;
        $this->subjectName = $subjectName;
        $this->facultyName = $facultyName;
        $this->roomNumber = $roomNumber;
    }
    
    // Getters
    public function getId() {
        return $this->id;
    }
    
    public function getSemester() {
        return $this->semester;
    }
    
    public function getCourse() {
        return $this->course;
    }
    
    public function getDay() {
        return $this->day;
    }
    
    public function getTimeStart() {
        return $this->timeStart;
    }
    
    public function getTimeEnd() {
        return $this->timeEnd;
    }
    
    public function getSubjectCode() {
        return $this->subjectCode;
    }
    
    public function getSubjectName() {
        return $this->subjectName;
    }
    
    public function getFacultyName() {
        return $this->facultyName;
    }
    
    public function getRoomNumber() {
        return $this->roomNumber;
    }
    
    // Convenience methods
    public function getTimeRange() {
        return $this->timeStart . ' - ' . $this->timeEnd;
    }
    
    public function getFullSubjectInfo() {
        return $this->subjectName . ' (' . $this->subjectCode . ')';
    }
    
    // Convert to array for JSON or other data formats
    public function toArray() {
        return [
            'id' => $this->id,
            'semester' => $this->semester,
            'course' => $this->course,
            'day' => $this->day,
            'timeStart' => $this->timeStart,
            'timeEnd' => $this->timeEnd,
            'subjectCode' => $this->subjectCode,
            'subjectName' => $this->subjectName,
            'facultyName' => $this->facultyName,
            'roomNumber' => $this->roomNumber
        ];
    }
}
?> 