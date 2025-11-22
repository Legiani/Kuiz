<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Kuizio</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Start Quiz</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="upload_test.php">Upload Test</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_tests.php">My Tests</a>
                </li>
            </ul>
            <?php if (isset($_SESSION['name'])): ?>
                <div class="d-flex align-items-center text-white">
                    <?php if (isset($_SESSION['picture'])): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['picture']); ?>" alt="Profile" class="rounded-circle me-2" style="width: 32px; height: 32px;">
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
