<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION["name"]);
$surname = htmlspecialchars($_SESSION["surname"]);

$mysqli = new mysqli("localhost", "root", "", "taskit");

if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

$user_id = $_SESSION["user_id"];

// Fetch tasks data
$tasksQuery = $mysqli->prepare("
    SELECT task_id, title, description, due_date, priority, status, created_at 
    FROM tasks 
    WHERE user_id = ?
    ORDER BY FIELD(status, 'Overdue', 'In Progress', 'Pending', 'Completed'), due_date ASC
");
$tasksQuery->bind_param("i", $user_id);
$tasksQuery->execute();
$tasksResult = $tasksQuery->get_result();

$allTasks = [];
while ($task = $tasksResult->fetch_assoc()) {
    $allTasks[] = $task;
}

// Close connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Task Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root {
  --primary-color: #4a6fa5;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --info-color: #17a2b8;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --sidebar-width: 80px;
  --sidebar-expanded-width: 250px;
  --navbar-height: 60px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background-color: #f5f5f5;
  color: #333;
  line-height: 1.6;
}

/* Main Layout Grid */
.app-container {
  display: grid;
  grid-template-columns: var(--sidebar-width) 1fr;
  min-height: 100vh;
}

/* Sidebar */
.sidebar {
  background-color: #e9ecef;
  height: 100vh;
  position: fixed;
  width: var(--sidebar-width);
  padding: 20px 0;
  transition: all 0.3s ease;
  z-index: 100;
  border-right: 1px solid #dee2e6;
}

.sidebar-menu {
  list-style: none;
  display: grid;
  gap: 10px;
  padding: 0;
}

.sidebar-menu li {
  display: flex;
  justify-content: center;
}

.sidebar-menu a {
  color: #495057;
  text-decoration: none;
  padding: 12px;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  transition: all 0.2s;
}

.sidebar-menu a.active {
  background-color: var(--primary-color);
  color: white;
}

.sidebar-menu a:hover:not(.active) {
  background-color: #dee2e6;
}

.sidebar-menu i {
  font-size: 1.25rem;
  margin-bottom: 4px;
}

.sidebar-menu span {
  font-size: 0.75rem;
  display: none;
}

.sidebar:hover {
  width: var(--sidebar-expanded-width);
}

.sidebar:hover .sidebar-menu span {
  display: block;
}

/* Main Content */
.main-content {
  grid-column: 2;
  padding: 20px;
  margin-left: var(--sidebar-width);
  transition: margin-left 0.3s;
}

.sidebar:hover ~ .main-content {
  margin-left: var(--sidebar-expanded-width);
}

/* Top Bar */
.top-bar {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  margin-bottom: 20px;
  gap: 20px;
}

.search-bar {
  display: flex;
  align-items: center;
  background-color: white;
  border-radius: 20px;
  padding: 8px 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  max-width: 400px;
}

.search-bar input {
  border: none;
  background: transparent;
  outline: none;
  width: 100%;
  padding: 5px 10px;
  font-size: 14px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background-color: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-rows: auto auto auto;
  gap: 20px;
}

/* First Row - 4 columns */
.first-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

/* Cards */
.card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.welcome-card {
  display: flex;
  align-items: center;
  justify-content: center;
}

.welcome-card h1 {
  color: var(--primary-color);
  font-size: 1.5rem;
}

.stats-card {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.stat-item {
  text-align: center;
}

.stat-item h3 {
  font-size: 14px;
  color: #666;
  margin-bottom: 5px;
}

.stat-item div {
  font-size: 24px;
  font-weight: bold;
}

.charts-card {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.chart-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.chart-container h4 {
  margin-bottom: 10px;
  font-size: 15px;
  color: var(--primary-color);
  font-weight: 600;
}

.chart-legend {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 10px;
}

.chart-legend span {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
}

.chart-legend-color {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

/* Calendar Card */
.calendar-card {
  display: flex;
  flex-direction: column;
}

.calendar-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.calendar-nav {
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px 10px;
  color: var(--primary-color);
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 5px;
}

.calendar-day-header {
  text-align: center;
  font-weight: bold;
  font-size: 0.8rem;
  padding: 5px 0;
  color: #666;
}

.calendar-day {
  text-align: center;
  padding: 8px;
  border-radius: 4px;
}

.calendar-day.today {
  background: var(--primary-color);
  color: white;
}

/* Second Row */
.second-row {
  display: grid;
  grid-template-columns: 1fr;
}

.charts-header {
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 20px;
}

.filter-buttons {
  display: grid;
  grid-template-columns: repeat(4, auto);
  gap: 10px;
}

  .filter-btn {
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 4px 10px;
    min-width: 0;
    font-size: 0.92rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
  }

.filter-btn.active {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Third Row - Tasks Table */
.third-row {
  display: grid;
  grid-template-columns: 1fr;
}

.task-view {
  display: grid;
  grid-template-rows: auto 1fr;
  gap: 20px;
}

.view-header {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
}

.create-task-btn {
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 4px;
  padding: 8px 15px;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.task-table {
  width: 100%;
  border-collapse: collapse;
}

.task-table th {
  text-align: left;
  padding: 12px 10px;
  border-bottom: 2px solid #e0e0e0;
  color: #666;
  font-weight: 500;
}

.task-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #f0f0f0;
}

.task-table tr:last-child td {
  border-bottom: none;
}

.task-table tr:hover td {
  background-color: #f9f9f9;
}

.status-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

.status-in-progress {
  background-color: #cce5ff;
  color: #004085;
}

.status-completed {
  background-color: #d4edda;
  color: #155724;
}

.status-overdue {
  background-color: #f8d7da;
  color: #721c24;
}

/* Floating Action Button */
.floating-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 50%;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  transition: all 0.3s;
  z-index: 800;
}

.floating-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .first-row {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .charts-card {
    grid-column: span 2;
  }
  
  .calendar-card {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .first-row {
    grid-template-columns: 1fr;
  }
  
  .filter-buttons {
    grid-template-columns: 1fr;
  }
  
  .app-container {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
  
  .main-content {
    margin-left: 0;
    grid-column: 1;
  }
  
  .top-bar {
    grid-template-columns: 1fr;
  }
  
  .search-bar {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .stats-card {
    grid-template-columns: 1fr;
  }
  
  .charts-card {
    grid-template-columns: 1fr;
  }
}
</style>
</head>

<body>
<div class="app-container">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <ul class="sidebar-menu">      
      <li><a href="#" class="active" title="Dashboard"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
      <li><a href="#" title="Completed"><i class="fas fa-check-circle"></i> <span>Completed</span></a></li>
      <li><a href="#" title="Goals"><i class="fas fa-bullseye"></i> <span>Goals</span></a></li>
      <li><a href="#" title="Diary"><i class="fas fa-book"></i> <span>Diary</span></a></li>
      <li><a href="#" title="Gantt"><i class="fas fa-project-diagram"></i> <span>Gantt</span></a></li>
      <li><a href="#" title="Deadline"><i class="fas fa-clock"></i> <span>Deadline</span></a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search tasks...">
      </div>
      
      <div class="user-profile">
        <span><?php echo htmlspecialchars($name . ' ' . $surname); ?></span>
        <div class="user-avatar">
          <?php echo strtoupper(substr($name, 0, 1) . substr($surname, 0, 1)); ?>
        </div>
      </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
      <!-- First Row -->
      <div class="first-row">
        <div class="card welcome-card">
          <h1>WELCOME <?php echo strtoupper(htmlspecialchars($name)); ?></h1>
        </div>
        
        <div class="card stats-card">
          <div class="stat-item">
            <h3>Total Tasks</h3>
            <div><?php echo count($allTasks); ?></div>
          </div>
          <div class="stat-item">
            <h3>Completed</h3>
            <div><?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'Completed')); ?></div>
          </div>
          <div class="stat-item">
            <h3>In Progress</h3>
            <div><?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'In Progress')); ?></div>
          </div>
          <div class="stat-item">
            <h3>Overdue</h3>
            <div><?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'Overdue')); ?></div>
          </div>
        </div>
        
        <div class="card charts-card">
          <div class="chart-container">
            <h4>Task Distribution by Status</h4>
            <canvas id="statusChart" width="170" height="170"></canvas>
            <div class="chart-legend">
              <span><span class="chart-legend-color" style="background:#28a745;"></span>Completed</span>
              <span><span class="chart-legend-color" style="background:#17a2b8;"></span>In Progress</span>
              <span><span class="chart-legend-color" style="background:#ffc107;"></span>Pending</span>
              <span><span class="chart-legend-color" style="background:#dc3545;"></span>Overdue</span>
            </div>
          </div>
          <div class="chart-container">
            <h4>Task Distribution by Priority</h4>
            <canvas id="priorityChart" width="170" height="170"></canvas>
            <div class="chart-legend">
              <span><span class="chart-legend-color" style="background:#dc3545;"></span>High</span>
              <span><span class="chart-legend-color" style="background:#ffc107;"></span>Medium</span>
              <span><span class="chart-legend-color" style="background:#28a745;"></span>Low</span>
            </div>
          </div>
        </div>
        
        <div class="card calendar-card">
          <div class="calendar-header">
            <button class="calendar-nav"><i class="fas fa-chevron-left"></i></button>
            <h2><?php echo date('F Y'); ?></h2>
            <button class="calendar-nav"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="calendar-grid">
            <?php 
            $dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($dayHeaders as $day): ?>
              <div class="calendar-day-header"><?php echo $day; ?></div>
            <?php endforeach;

            $firstDay = date('w', strtotime('first day of this month'));
            $daysInMonth = date('t');
            $currentDay = 1;

            for ($i = 0; $i < $firstDay; $i++): ?>
              <div></div>
            <?php endfor;

            while ($currentDay <= $daysInMonth): ?>
              <div class="calendar-day <?php echo $currentDay == date('j') ? 'today' : ''; ?>">
                <?php echo $currentDay; ?>
              </div>
              <?php $currentDay++;
            endwhile; ?>
          </div>
        </div>
      </div>

      <!-- Second Row -->
      <div class="second-row">
        <div class="card charts-header">
          <h2>Charts</h2>
          <div class="filter-buttons">
            <button class="filter-btn active"><i class="fas fa-list"></i> Recent Tasks</button>
            <button class="filter-btn"><i class="fas fa-spinner"></i> In Progress</button>
            <button class="filter-btn"><i class="fas fa-check"></i> Completed</button>
            <button class="filter-btn"><i class="fas fa-exclamation-triangle"></i> Overdue</button>
          </div>
        </div>
      </div>

      <!-- Third Row -->
      <div class="third-row">
        <div class="card task-view">
          <div class="view-header">
            <h2>Recent Tasks</h2>
            <button class="create-task-btn" id="createTaskBtn"><i class="fas fa-plus"></i> Create Task</button>
          </div>
          <table class="task-table">
            <thead>
              <tr>
                <th>Task</th>
                <th>Status</th>
                <th>Due Date</th>
              </tr>
            </thead>
            <tbody>
              <?php 
                $visibleTasks = array_slice($allTasks, 0, 5);
                foreach ($visibleTasks as $task): 
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($task['title']); ?></td>
                  <td>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                      <?php echo htmlspecialchars($task['status']); ?>
                    </span>
                  </td>
                  <td><?php echo $task['due_date'] ? date("M d, Y", strtotime($task['due_date'])) : 'No deadline'; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Floating Action Button -->
<button class="floating-btn" id="floatingActionBtn">
  <i class="fas fa-plus"></i>
</button>

<script>
// DOM Elements
const sidebar = document.getElementById('sidebar');
const floatingBtn = document.getElementById('floatingActionBtn');
const createTaskBtn = document.getElementById('createTaskBtn');

// Toggle sidebar for mobile
function handleResize() {
  if (window.innerWidth < 768) {
    sidebar.classList.remove('active');
  }
}

window.addEventListener('resize', handleResize);
handleResize(); // Initialize

// Chart.js Implementation
function renderCharts() {
  const tasks = <?php echo json_encode($allTasks); ?>;

  // Status Chart
  const statusCtx = document.getElementById('statusChart');
  if (statusCtx) {
    new Chart(statusCtx, {
      type: 'pie',
      data: {
        labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
        datasets: [{
          data: [
            countByStatus(tasks)['Completed'],
            countByStatus(tasks)['In Progress'],
            countByStatus(tasks)['Pending'],
            countByStatus(tasks)['Overdue']
          ],
          backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
  }

  // Priority Chart
  const priorityCtx = document.getElementById('priorityChart');
  if (priorityCtx) {
    new Chart(priorityCtx, {
      type: 'doughnut',
      data: {
        labels: ['High', 'Medium', 'Low'],
        datasets: [{
          data: [
            countByPriority(tasks)['High'],
            countByPriority(tasks)['Medium'],
            countByPriority(tasks)['Low']
          ],
          backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
          borderWidth: 0,
          cutout: '65%'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
  }
}

function countByStatus(tasks) {
  const counts = { 
    'Completed': 0, 
    'In Progress': 0, 
    'Pending': 0, 
    'Overdue': 0 
  };
  
  tasks.forEach(task => {
    if (task.status && counts.hasOwnProperty(task.status)) {
      counts[task.status]++;
    }
  });
  
  return counts;
}

function countByPriority(tasks) {
  const counts = { 
    'High': 0, 
    'Medium': 0, 
    'Low': 0 
  };
  
  tasks.forEach(task => {
    if (task.priority && counts.hasOwnProperty(task.priority)) {
      counts[task.priority]++;
    }
  });
  
  return counts;
}

document.addEventListener('DOMContentLoaded', function() {
  renderCharts();
});
</script>
</body>
</html>