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
  /* Reset & Base Styles */
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
    /* Unsplash background image */
    background-image: linear-gradient(rgba(30,40,60,0.18), rgba(30,40,60,0.18)), url('https://images.unsplash.com/photo-1681907285197-d314f53cc434?q=80&w=1332&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center bottom;
  }

  /* Navbar */
  .navbar {
    background-color: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.3);
    height: var(--navbar-height);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: none;
  }

  .search-bar {
    display: flex;
    align-items: center;
    background-color: #f0f0f0;
    border-radius: 20px;
    padding: 8px 15px;
    width: 400px;
    transition: all 0.3s;
  }

  .search-bar:focus-within {
    box-shadow: 0 0 0 2px rgba(74, 111, 165, 0.2);
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
    gap: 15px;
  }

  .user-name {
    font-weight: 500;
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
    cursor: pointer;
    transition: transform 0.2s;
  }

  .user-avatar:hover {
    transform: scale(1.05);
  }

  /* Layout */
  .container {
    display: grid;
    grid-template-columns: var(--sidebar-width) 1fr;
    min-height: 100vh;
    padding-top: var(--navbar-height);
  }

  /* Sidebar */
  .sidebar {
    background-color: rgba(44, 62, 80, 0.3);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    color: #ecf0f1;
    height: calc(100vh - var(--navbar-height));
    position: fixed;
    width: var(--sidebar-width);
    padding: 20px 0;
    transition: all 0.3s ease;
    z-index: 900;
    overflow: hidden;
  }

  .sidebar, .sidebar * {
    text-shadow: 0 1px 3px rgba(0,0,0,0.7);
  }

  .sidebar:hover {
    width: var(--sidebar-expanded-width);
  }

  .sidebar-header {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 0 30px 10px;
    gap: 10px;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 30px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
  }

  .sidebar-header span {
    opacity: 0;
    transition: opacity 0.2s;
  }

  .sidebar:hover .sidebar-header span {
    opacity: 1;
  }

  .sidebar-menu {
    list-style: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
  }

  .sidebar:hover .sidebar-menu {
    align-items: flex-start;
  }

  .sidebar-menu li {
    margin-bottom: 5px;
    width: 100%;
    display: flex;
    justify-content: flex-start;
    padding-left: 10px;
  }

  .sidebar-menu a {
    text-decoration: none;
    color: #ecf0f1;
    display: flex;
    align-items: center;
    padding: 12px 14px; 
    border-radius: 8px;
    transition: all 0.3s;
    margin: 0 10px;
    white-space: nowrap;
    width: calc(100% - 20px);
    justify-content: flex-start;
  }

  .sidebar-menu a span {
    opacity: 0;
    transition: opacity 0.2s;
  }

  .sidebar:hover .sidebar-menu a span {
    opacity: 1;
  }

  .sidebar-menu a:hover, .sidebar-menu a.active {
    background-color: rgba(74, 111, 165, 0.7);
    color: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .sidebar-menu i {
    margin-right: 10px;
    font-size: 1rem;
    min-width: 20px;
    text-align: center;
  }

  .sidebar:hover .sidebar-menu i {
    margin-right: 10px;
  }

  .sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: center;
  }

  .sidebar:hover .sidebar-footer {
    justify-content: flex-start;
  }

  .collapse-btn {
    background: none;
    border: none;
    color: #ecf0f1;
    cursor: pointer;
    display: flex;
    align-items: center;
    width: 100%;
    padding: 10px;
    transition: all 0.3s;
    white-space: nowrap;
    justify-content: center;
    border-radius: 8px;
  }

  .sidebar:hover .collapse-btn {
    justify-content: flex-start;
    padding: 10px 20px;
  }

  .collapse-btn:hover {
    background-color: rgba(255,255,255,0.1);
  }

  .collapse-btn span {
    opacity: 0;
    transition: opacity 0.2s;
  }

  .sidebar:hover .collapse-btn span {
    opacity: 1;
  }

  .collapse-btn i {
    flex-shrink: 0;
  }

  /* Main Content */
  .main-content {
    padding: 20px 40px;
    margin-left: var(--sidebar-width);
    min-height: calc(100vh - var(--navbar-height));
    transition: margin-left 0.3s;
  }

  .sidebar:hover ~ .main-content {
    margin-left: var(--sidebar-expanded-width);
  }

  /* Card Styling */
  .card {
    background: rgba(255,255,255,0.82);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    min-width: 300px;
  }

  /* Opacity for dashboard cards */
  .welcome-block,
  .stats-block,
  .pie-charts,
  .charts-header,
  .calendar-card,
  .line-graph,
  .right-card,
  .task-view {
    background: rgba(255,255,255,0.93) !important;
    /* Keep border-radius and shadow for card look */
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    /* Remove any solid background overrides */
  }


  .welcome-block{
    min-width:400px;
    text-align: center;
    max-height: 150px;
  }
  /* Page Header */
  .page-header {
    margin-bottom: 20px;
  }

  .page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 10px;
  }

  .action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
  }

  .action-btn {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px 15px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
  }

  .action-btn:hover {
    background-color: #f0f0f0;
  }

  .action-btn.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }

  .action-btn i {
    font-size: 0.9rem;
  }

  /* Task View Section */
  .task-view {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    width: 100%;
    min-width: 900px;
    margin: 0 auto;
    box-sizing: border-box;
  }

  .view-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .view-title {
    font-weight: 500;
    margin: 0;
    color: #666;
    line-height: 1.4;
  }

  .view-actions button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 8px 15px;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .view-actions button:hover {
    background-color: #3a5a80;
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

  /* Right Side Card */
  .right-card {
    background: rgba(255,255,255,0.75);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* Line Graph */
  .line-graph {
    background: rgba(255,255,255,0.75);
    border-radius: 8px;
    padding: 20px;
  }

  /* Calendar */
  .calendar {
    background: rgba(255,255,255,0.75);
    border-radius: 8px;
    padding: 20px;
    
  }

  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
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

  /* Task Modal */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
  }

  .modal-overlay.active {
    opacity: 1;
    visibility: visible;
  }

  .modal-content {
    background-color: white;
    border-radius: 8px;
    width: 500px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px);
    transition: all 0.3s;
  }

  .modal-overlay.active .modal-content {
    transform: translateY(0);
  }

  .modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-title {
    font-weight: 600;
    font-size: 1.25rem;
  }

  .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--secondary-color);
  }

  .modal-body {
    padding: 20px;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
  }

  .form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(74, 111, 165, 0.2);
  }

  .btn {
    padding: 10px 20px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .btn-primary:hover {
    background-color: #3a5a80;
  }

  .btn-secondary {
    background-color: var(--secondary-color);
    color: white;
  }

  .btn-secondary:hover {
    background-color: #5a6268;
  }
  .fade-task {
    opacity: 0.4;
    transition: opacity 0.5s;
  }
  .fade-task-more {
    opacity: 0.18;
  }

  /* Responsive */
  @media (max-width: 992px) {
    .sidebar {
      transform: translateX(-100%);
    }
    
    .sidebar.active {
      transform: translateX(0);
      width: var(--sidebar-expanded-width);
    }
    
    .main-content {
      margin-left: 0;
      padding: 20px;
    }
    
    .search-bar {
      width: 200px;
    }

    .third-row {
      flex-direction: column;
    }
  }

  @media (max-width: 768px) {
    .task-view {
      min-width: 100%;
    }
    
    .task-table th, 
    .task-table td {
      padding: 8px;
      font-size: 0.9rem;
    }
    
    .status-badge {
      font-size: 0.7rem;
      padding: 3px 8px;
    }
  }

  @media (max-width: 576px) {
    .navbar {
      padding: 0 10px;
    }
    
    .search-bar {
      width: 150px;
    }
    
    .user-name {
      display: none;
    }
    
    .action-buttons {
      overflow-x: auto;
      padding-bottom: 10px;
    }
    
    .action-btn {
      flex-shrink: 0;
    }
  }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  </head>

  <body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search tasks...">
    </div>
    
    <div class="user-profile">
      <span class="user-name"><?php echo htmlspecialchars($name . ' ' . $surname); ?></span>
      <div class="user-avatar" title="<?php echo htmlspecialchars($name . ' ' . $surname); ?>">
        <?php echo strtoupper(substr($name, 0, 1) . substr($surname, 0, 1)); ?>
      </div>
    </div>
  </nav>

  <div class="container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <i class="fas fa-tasks"></i>
        <span>Taskit vc</span>
      </div>
      <ul class="sidebar-menu">      
        <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="#"><i class="fas fa-check-circle"></i> <span>Completed</span></a></li>
        <li><a href="#"><i class="fas fa-bullseye"></i> <span>Goals</span></a></li>
        <li><a href="#"><i class="fas fa-book"></i> <span>Diary</span></a></li>
        <li><a href="#"><i class="fas fa-project-diagram"></i> <span>Gantt</span></a></li>
        <li><a href="#"><i class="fas fa-clock"></i> <span>Deadline</span></a></li>
      </ul>
      <div class="sidebar-footer">
        <button class="collapse-btn" id="collapseSidebar">
          <i class="fas fa-chevron-left"></i>
          <span>Collapse</span>
        </button>
      </div>
    </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Dashboard Top Area: Grid for Welcome, Stats, Pie/Donut, Calendar spanning two rows -->
    <div class="dashboard-top-area" style="position: relative; min-height: 220px;">
      <div class="top-row" style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 20px; margin-bottom: 20px; align-items: stretch;">
        <div class="welcome-block" style="background: rgba(255,255,255,0.75); padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: center;">
          <h1 class="page-title">WELCOME <?php echo strtoupper(htmlspecialchars($name)); ?></h1>
        </div>
        <div class="pie-charts" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: rgba(255,255,255,0.93); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px 18px; min-width: 520px; min-height: 260px; align-items: stretch; justify-items: center; margin-left: 40px;">
          <div style="display: flex; flex-direction: column; align-items: center; width: 100%;">
            <h4 style="margin-bottom: 10px; font-size: 15px; color: #4a6fa5; font-weight: 600;">Task Distribution by Status</h4>
            <div style="width: 170px; height: 170px;">
              <canvas id="statusChart" width="170" height="170"></canvas>
            </div>
            <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#28a745;border-radius:2px;"></span>Completed</span>
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#17a2b8;border-radius:2px;"></span>In Progress</span>
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#ffc107;border-radius:2px;"></span>Pending</span>
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:2px;"></span>Overdue</span>
            </div>
          </div>
          <div style="display: flex; flex-direction: column; align-items: center; width: 100%;">
            <h4 style="margin-bottom: 10px; font-size: 15px; color: #4a6fa5; font-weight: 600;">Task Distribution by Priority</h4>
            <div style="width: 170px; height: 170px;">
              <canvas id="priorityChart" width="170" height="170"></canvas>
            </div>
            <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:2px;"></span>High</span>
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#ffc107;border-radius:2px;"></span>Medium</span>
              <span style="display: flex; align-items: center; gap: 5px;"><span style="display:inline-block;width:12px;height:12px;background:#28a745;border-radius:2px;"></span>Low</span>
            </div>
          </div>
        </div>
        <div></div>
      </div>
      <div class="second-row" style="display: flex; justify-content: flex-start; gap: 20px; margin-bottom: 20px;">
        <div class="second-row-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; width: 100%; align-items: stretch; margin-bottom: 20px;">
          <div class="stats-block" style="background: rgba(255,255,255,0.75); padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: center; min-width: 500px; height:150px; text-align:center;">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
              <div>
                <h3 style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Tasks</h3>
                <div style="font-size: 24px; font-weight: bold;"><?php echo count($allTasks); ?></div>
              </div>
              <div>
                <h3 style="font-size: 14px; color: #666; margin-bottom: 5px;">Completed</h3>
                <div style="font-size: 24px; font-weight: bold;">
                  <?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'Completed')); ?>
                </div>
              </div>
              <div>
                <h3 style="font-size: 14px; color: #666; margin-bottom: 5px;">In Progress</h3>
                <div style="font-size: 24px; font-weight: bold;">
                  <?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'In Progress')); ?>
                </div>
              </div>
              <div>
                <h3 style="font-size: 14px; color: #666; margin-bottom: 5px;">Overdue</h3>
                <div style="font-size: 24px; font-weight: bold;">
                  <?php echo count(array_filter($allTasks, fn($task) => $task['status'] === 'Overdue')); ?>
                </div>
              </div>
            </div>
          </div>
          <div></div>
        </div>
      </div>

    </div>

  <style>


/* Add this to your CSS */
.calendar-card {
  height: 800px; /* Set your desired fixed height */
  min-height: 400px; /* Prevents collapsing if empty */
  display: flex;
  flex-direction: column;
  background: rgba(255,255,255,0.97);
  border-radius: 14px;
  box-shadow: 0 4px 24px 0 rgba(74,111,165,0.10);
  padding: 20px;
  margin-bottom: 20px;
}

.calendar {
  flex: 1; /* Makes calendar fill available space */
  overflow-y: auto; /* Adds scroll if content overflows */
  display: flex;
  flex-direction: column;
}
.right-card{
  min-width: 100px;
}
.calendar-grid {
  flex: 1; /* Makes the grid fill the calendar space */
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 5px;
}erflow-y: auto; /* Adds scroll if content overflows */
    .dashboard-top-area {
      position: relative;
      min-height: 220px;
      margin-bottom: 20px;
    }
    .third-row {
      display: flex;
      gap: 20px;
      align-items: stretch;
    }
    .right-column {
      min-width: 270px;
      max-width: 320px;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 20px;
      align-items: stretch;
      box-sizing: border-box;
      margin-right: 0;
    }
    .calendar-card, .line-graph {
      width: 100%;
      min-width: 240px;
      max-width: 100%;
      align-self: stretch;
      box-sizing: border-box;
      margin-right: 0;
    }
    @media (max-width: 992px) {
      .dashboard-top-area {
        min-height: unset;
      }
      .third-row {
        flex-direction: column;
      }
      .right-column {
        max-width: 100%;
        min-width: 0;
        width: 100%;
        margin-right: 0;
      }
      .calendar-card, .line-graph {
        max-width: 100%;
        min-width: 0;
        width: 100%;
        margin-right: 0;
      }
      .calendar-card{
        min-width: 910px;
      }
    }
  </style>
    <!-- Third Row: Two main cards side by side -->
    <div class="third-row" style="display: flex; gap: 20px; align-items: stretch">
      <div class="third-row-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; width: 100%; align-items: stretch;">
        <!-- Left Card - Tasks (2/3 width) -->
        <div class="task-view" style="width: 100%;">
          <div class="view-header">
            <p class="view-title">Recent Tasks</p>
            <div class="view-actions">
              <button id="createTaskBtn"><i class="fas fa-plus"></i> Create Task</button>
            </div>
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
                foreach ($visibleTasks as $i => $task): 
                  $fadeClass = '';
                  if ($i === 3) $fadeClass = 'fade-task';
                  if ($i === 4) $fadeClass = 'fade-task fade-task-more';
              ?>
                <tr<?php if ($fadeClass) echo ' class="' . $fadeClass . '"'; ?>
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
        <!-- Right Column: Calendar only -->
        <div class="right-column" style="display: flex; flex-direction: column; gap: 20px; align-items: stretch; min-width: 270px; max-width: 320px; width: 100%; box-sizing: border-box; margin-right: 0;">   
          <div class="calendar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
              <h3 style="margin: 0;">Calendar</h3>
              <div>
                <button class="calendar-nav" style="background: none; border: none; cursor: pointer; padding: 5px 10px;">
                  <i class="fas fa-chevron-left"></i>
                </button>
                <span style="font-weight: 500;"><?php echo date('F Y'); ?></span>
                <button class="calendar-nav" style="background: none; border: none; cursor: pointer; padding: 5px 10px;">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>
            </div>
            <div class="calendar-grid">
              <?php 
              $dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
              foreach ($dayHeaders as $day): ?>
                <div style="text-align: center; font-weight: bold; font-size: 0.8rem; padding: 5px 0;">
                  <?php echo $day; ?>
                </div>
              <?php endforeach;

              $firstDay = date('w', strtotime('first day of this month'));
              $daysInMonth = date('t');
              $currentDay = 1;

              for ($i = 0; $i < $firstDay; $i++): ?>
                <div style="height: 30px;"></div>
              <?php endfor;

              while ($currentDay <= $daysInMonth): ?>
                <div style="text-align: center; padding: 5px;
                    <?php
                      // Highlight today only if it's the current day of the current month and year
                      if ($currentDay == date('j')) {
                        echo 'background: var(--primary-color); color: white; border-radius: 50%;';
                      }
                    ?>">
                  <?php echo $currentDay; ?>
                </div>
                <?php $currentDay++;
              endwhile; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating Action Button -->
  <button class="floating-btn" id="floatingActionBtn">
    <i class="fas fa-plus"></i>
  </button>

  <!-- Task Modal -->
  <div class="modal-overlay" id="taskModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Create New Task</div>
        <button class="modal-close" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="taskForm">
          <div class="form-group">
            <label for="taskTitle">Title</label>
            <input type="text" id="taskTitle" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="taskDescription">Description</label>
            <textarea id="taskDescription" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label for="taskDueDate">Due Date</label>
            <input type="datetime-local" id="taskDueDate" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="taskPriority">Priority</label>
            <select id="taskPriority" class="form-control" required>
              <option value="Low">Low</option>
              <option value="Medium" selected>Medium</option>
              <option value="High">High</option>
            </select>
          </div>
          <div class="form-group">
            <label for="taskStatus">Status</label>
            <select id="taskStatus" class="form-control" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
          <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Task</button>
            <button type="button" class="btn btn-secondary" id="cancelTask">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  // DOM Elements
  const sidebar = document.getElementById('sidebar');
  const collapseBtn = document.getElementById('collapseSidebar');
  const floatingBtn = document.getElementById('floatingActionBtn');
  const createTaskBtn = document.getElementById('createTaskBtn');
  const taskModal = document.getElementById('taskModal');
  const closeModal = document.getElementById('closeModal');
  const cancelTask = document.getElementById('cancelTask');
  const taskForm = document.getElementById('taskForm');

  // Toggle sidebar collapse
  collapseBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    
    // Update icon and text
    const icon = collapseBtn.querySelector('i');
    const text = collapseBtn.querySelector('span');
    
    if (sidebar.classList.contains('active')) {
      icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
      text.textContent = 'Collapse';
    } else {
      icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
      text.textContent = 'Expand';
    }
  });

  // Toggle task modal
  function toggleModal() {
    taskModal.classList.toggle('active');
  }

  [floatingBtn, createTaskBtn, closeModal, cancelTask].forEach(btn => {
    btn.addEventListener('click', toggleModal);
  });

  // Close modal when clicking outside
  taskModal.addEventListener('click', (e) => {
    if (e.target === taskModal) {
      toggleModal();
    }
  });

  // Form submission
  taskForm.addEventListener('submit', (e) => {
    e.preventDefault();
    // Here you would handle form submission via AJAX
    alert('Task would be saved here in a real implementation');
    toggleModal();
    taskForm.reset();
  });

  // Responsive sidebar toggle for mobile
  function handleResize() {
    if (window.innerWidth < 992) {
      sidebar.classList.remove('active');
    } else {
      sidebar.classList.add('active');
    }
  }

  window.addEventListener('resize', handleResize);
  handleResize(); // Initialize

  // Calendar navigation
  const calendarNavs = document.querySelectorAll('.calendar-nav');
  let currentMonth = new Date().getMonth();
  let currentYear = new Date().getFullYear();

  calendarNavs.forEach(btn => {
    btn.addEventListener('click', function() {
      const monthDisplay = this.parentElement.querySelector('span');
      
      if (this.querySelector('.fa-chevron-left')) {
        currentMonth--;
        if (currentMonth < 0) {
          currentMonth = 11;
          currentYear--;
        }
      } else {
        currentMonth++;
        if (currentMonth > 11) {
          currentMonth = 0;
          currentYear++;
        }
      }
      
      const date = new Date(currentYear, currentMonth, 1);
      monthDisplay.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });
      updateCalendarDays(date);
    });
  });

  function updateCalendarDays(date) {
    const firstDay = date.getDay();
    const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const calendarGrid = document.querySelector('.calendar-grid');
    const dayCells = Array.from(calendarGrid.children).slice(7); // Skip day headers
    
    // Clear existing days
    dayCells.forEach(cell => cell.textContent = '');
    
    // Fill empty cells before first day
    for (let i = 0; i < firstDay; i++) {
      dayCells[i].textContent = '';
      dayCells[i].style.background = 'transparent';
    }
    
    // Fill days of month
    let currentDay = 1;
    for (let i = firstDay; i < firstDay + daysInMonth; i++) {
      dayCells[i].textContent = currentDay;
      
      // Highlight today
      const today = new Date();
      if (currentDay == today.getDate() && 
          date.getMonth() == today.getMonth() && 
          date.getFullYear() == today.getFullYear()) {
        dayCells[i].style.background = 'var(--primary-color)';
        dayCells[i].style.color = 'white';
        dayCells[i].style.borderRadius = '50%';
      } else {
        dayCells[i].style.background = 'transparent';
        dayCells[i].style.color = 'inherit';
      }
      
      currentDay++;
    }
  }

  // ====== Chart.js Pie Charts ======
  function countByStatus(tasks) {
    const counts = { 
      'Completed': 0, 
      'In Progress': 0, 
      'Pending': 0, 
      'Overdue': 0 
    };
    
    if (!tasks || !Array.isArray(tasks)) return counts;
    
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
    
    if (!tasks || !Array.isArray(tasks)) return counts;
    
    tasks.forEach(task => {
      if (task.priority && counts.hasOwnProperty(task.priority)) {
        counts[task.priority]++;
      }
    });
    
    return counts;
  }

  function renderCharts() {
    const tasks = <?php echo json_encode($allTasks); ?>;

    // Debug: Verify data
    console.log("Tasks data:", tasks);
    if (!tasks || tasks.length === 0) {
      console.warn("No tasks found");
      return;
    }

    // Status Chart
  const statusData = countByStatus(tasks);
    console.log("Status counts:", statusData);
    
  const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
      new Chart(statusCtx, {
        type: 'pie',
        data: {
          labels: Object.keys(statusData).filter(k => statusData[k] > 0),
          datasets: [{
            data: Object.values(statusData).filter(v => v > 0),
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  // Show label and percent
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const value = context.raw;
                  const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                  return `${context.label}: ${value} (${percent}%)`;
                }
              }
            },
            datalabels: {
              color: '#333',
              font: { weight: 'bold', size: 13 },
              formatter: function(value, context) {
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percent = total ? (value / total) * 100 : 0;
                return percent > 0 ? percent.toFixed(0) + '%' : '';
              }
            }
          }
        },
        plugins: [ChartDataLabels]
      });
    }

  const priorityData = countByPriority(tasks);
    console.log("Priority counts:", priorityData);
    
  const priorityCtx = document.getElementById('priorityChart');
    if (priorityCtx) {
      new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
          labels: Object.keys(priorityData).filter(k => priorityData[k] > 0),
          datasets: [{
            data: Object.values(priorityData).filter(v => v > 0),
            backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
            borderWidth: 0,
            cutout: '65%'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const value = context.raw;
                  const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                  return `${context.label}: ${value} (${percent}%)`;
                }
              }
            },
            datalabels: {
              color: '#333',
              font: { weight: 'bold', size: 13 },
              formatter: function(value, context) {
                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const percent = total ? (value / total) * 100 : 0;
                return percent > 0 ? percent.toFixed(0) + '%' : '';
              }
            }
          }
        },
        plugins: [ChartDataLabels]
      });
    }
  }

  function renderCompletionChart() {
    // Sample data - replace with your actual completion data
    const ctx = document.getElementById('completionChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array.from({length: 30}, (_, i) => i+1),
        datasets: [{
          label: 'Tasks Completed',
          data: Array.from({length: 30}, () => Math.floor(Math.random() * 10)),
          borderColor: '#4a6fa5',
          backgroundColor: 'rgba(74, 111, 165, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    renderCharts();
    renderCompletionChart();
  });
  </script>
  </body>
  </html>