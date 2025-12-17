<?php
/********************
 * DATABASE CONFIG
 ********************/
$servername = "mariadb";      // ECS MariaDB host
$username   = "cs332gXX";     // replace with our account
$password   = "YOUR_PASSWORD"; // replace with out password
$database   = "cs332gXX";     // same as username

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/********************
 * READ FORM INPUTS
 ********************/
$profSSN      = isset($_GET['prof_ssn']) ? intval($_GET['prof_ssn']) : null;
$studentCWID  = isset($_GET['student_cwid']) ? intval($_GET['student_cwid']) : null;
$courseNumber = isset($_GET['course_number']) ? intval($_GET['course_number']) : null;
$sectionNum   = isset($_GET['section_number']) ? intval($_GET['section_number']) : null;
?>

<!DOCTYPE html>
<html>
    <head>
        <title>CS332 Database Project</title>
        <style>
            body { font-family: Arial; }
            .box { border: 1px solid #ccc; padding: 15px; margin: 15px 0; }
        </style>
    </head>
    <body>

        <h1>CS332 Course Information System</h1>

        <!-- ================= PROFESSOR SCHEDULE ================= -->
        <div class="box">
            <h2>Professor Teaching Schedule</h2>
            <form method="get">
            Professor SSN: <input type="text" name="prof_ssn">
            <input type="submit" value="Search">
            </form>

            <?php
            if ($profSSN) {
                echo "<h3>Schedule:</h3>";
                
                $stmt = $conn->prepare(
                    "SELECT Courses.Course_Title, Section.Classroom, Section.Meeting_Days_Times
                    FROM Section
                    JOIN Courses ON Section.Course_Number = Courses.Course_Number
                    WHERE Section.Prof_SSN = ?"
                );

                $stmt->bind_param("i", $profSSN);
                $stmt->execute();
                $stmt->bind_result($title, $room, $time);

                echo "<ul>";
                while ($stmt->fetch()) {
                    echo "<li>$title — Room: $room — Time: $time</li>";
                }
                echo "</ul>";
                
                $stmt->close();
            }
            ?>
        </div>

        <!-- ================= STUDENT HISTORY ================= -->
        <div class="box">
            <h2>Student Course History</h2>
            <form method="get">
            Student CWID: <input type="text" name="student_cwid">
            <input type="submit" value="Search">
            </form>

            <?php
            if ($studentCWID) {
                echo "<h3>Completed Courses:</h3>";

                $stmt = $conn->prepare(
                    "SELECT Courses.Course_Title, Enrollment.Grade
                    FROM Enrollment
                    JOIN Courses ON Enrollment.Course_Number = Courses.Course_Number
                    WHERE Enrollment.Student_CWID = ?"
                );

                $stmt->bind_param("i", $studentCWID);
                $stmt->execute();
                $stmt->bind_result($title, $grade);

                echo "<ul>";
                while ($stmt->fetch()) {
                    echo "<li>$title — Grade: $grade</li>";
                }
                echo "</ul>";

                $stmt->close();
            }
            ?>
        </div>

        <!-- ================= COURSE SECTIONS ================= -->
        <div class="box">
            <h2>Course Sections</h2>
            <form method="get">
            Course Number: <input type="text" name="course_number">
            <input type="submit" value="View Sections">
            </form>

            <?php
            if ($courseNumber) {
                // Get course title
                $stmt = $conn->prepare("SELECT Course_Title FROM Courses WHERE Course_Number = ?");
                $stmt->bind_param("i", $courseNumber);
                $stmt->execute();
                $stmt->bind_result($courseTitle);

                if ($stmt->fetch()) {
                    echo "<h3>$courseTitle</h3>";
                } else {
                    echo "<p>No such course found.</p>";
                }
                $stmt->close();

                // Get sections
                $stmt = $conn->prepare(
                    "SELECT Sec_Number, Classroom, Meeting_Days_Times
                    FROM Section
                    WHERE Course_Number = ?"
                );
                $stmt->bind_param("i", $courseNumber);
                $stmt->execute();
                $stmt->bind_result($sec, $room, $time);

                echo "<ul>";
                while ($stmt->fetch()) {
                    echo "<li>Section $sec — Room: $room — Time: $time</li>";
                }
                echo "</ul>";

                $stmt->close();
            }
            ?>
        </div>

        <!-- ================= GRADE DISTRIBUTION ================= -->
        <div class="box">
            <h2>Grade Distribution</h2>
            <form method="get">
            Course Number: <input type="text" name="course_number">
            Section Number: <input type="text" name="section_number">
            <input type="submit" value="Get Grades">
            </form>

            <?php
            if ($courseNumber && $sectionNum) {
                $stmt = $conn->prepare(
                    "SELECT Grade, COUNT(*)
                    FROM Enrollment
                    WHERE Course_Number = ? AND Sec_Number = ?
                    GROUP BY Grade"
                );

                $stmt->bind_param("ii", $courseNumber, $sectionNum);
                $stmt->execute();
                $stmt->bind_result($grade, $count);

                echo "<ul>";
                while ($stmt->fetch()) {
                    echo "<li>$grade: $count students</li>";
                }
                echo "</ul>";

                $stmt->close();
            }
            ?>
        </div>

        <?php $conn->close(); ?>

    </body>
</html>
