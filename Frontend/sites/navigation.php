<?php // Hier war ein Sessionstart --> direkt zu login.php verschoben ?> 
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Zeitwert</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION["Benutzername"])): ?>
                    <li class="nav-item">
                        <span class="nav-link">👤 <?php echo htmlspecialchars($_SESSION["Benutzername"]); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger ms-2" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-2" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
