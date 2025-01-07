<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Charset -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Dynamic SEO Meta Tags -->
    <title>
        <?php echo isset($metaTitle) ? $metaTitle : 'Service Ads Classified Platform | Find Services Near You'; ?>
    </title>
    <meta name="description" content="<?php echo isset($metaDescription) ? $metaDescription : 'Explore categories of service ads to find trusted providers for home repairs, cleaning, legal assistance, and more.'; ?>">
    <meta name="keywords" content="<?php echo isset($metaKeywords) ? $metaKeywords : 'service ads, classified ads, local services, skilled professionals, trusted providers'; ?>">
    <meta name="author" content="Chima Elvis">
    <meta name="robots" content="index, follow">

    <!-- Open Graph Tags for Social Media -->
    <meta property="og:title" content="<?php echo isset($metaTitle) ? $metaTitle : 'Service Ads Classified Platform | Find Services Near You'; ?>">
    <meta property="og:description" content="<?php echo isset($metaDescription) ? $metaDescription : 'Explore categories of service ads to find trusted providers for home repairs, cleaning, legal assistance, and more.'; ?>">
    <meta property="og:image" content="<?php echo isset($metaImage) ? $metaImage : 'https://www.example.com/images/default-thumbnail.jpg'; ?>">
    <meta property="og:url" content="<?php echo isset($siteUrl) ? $siteUrl : 'https://www.example.com/'; ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($metaTitle) ? $metaTitle : 'Service Ads Classified Platform | Find Services Near You'; ?>">
    <meta name="twitter:description" content="<?php echo isset($metaDescription) ? $metaDescription : 'Explore categories of service ads to find trusted providers for home repairs, cleaning, legal assistance, and more.'; ?>">
    <meta name="twitter:image" content="<?php echo isset($metaImage) ? $metaImage : 'https://www.example.com/images/default-thumbnail.jpg'; ?>">

    <!-- Favicon -->
    <link rel="icon" href="/images/favicon.ico">

    <!-- CSS Files -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <!-- Dynamic JSON-LD Structured Data for SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "<?php echo isset($siteName) ? $siteName : 'Service Ads Classified Platform'; ?>",
      "url": "<?php echo isset($siteUrl) ? $siteUrl : 'https://www.example.com'; ?>",
      "description": "<?php echo isset($metaDescription) ? $metaDescription : 'Discover trusted service providers through categorized service ads. Browse home repairs, cleaning, legal, and business services.'; ?>",
      "potentialAction": 
      {
        "@type": "SearchAction",
        "target": "<?php echo isset($searchTarget) ? $searchTarget : 'https://www.example.com/search?query={search_term_string}'; ?>",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

</head>
<body>
<header id="nav-header">
    <nav>
        <!-- Logo Image -->
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>index.php">
                <img src="<?php echo BASE_URL; ?>images/logo.png" alt="Services Ads Classified Logo">
            </a>
        </div>
        <!-- Navigation Links -->
        <ul class="nav-links">
            <li>
                <a href="#" title="My Ads" class="my-ads">
                    <i class="fas fa-th-list"></i> My Ads
                </a>
            </li>
            <li>
                <a href="#" title="Favorite" class="favorite">
                    <i class="fas fa-heart"></i> Favorite
                </a>
            </li>
            <li>
                <a href="#" title="Message" class="message">
                    <i class="fas fa-envelope"></i> Message
                </a>
            </li>
            <li><a href="#" title="Notification" class="notification">
                    <i class="fas fa-bell"></i> Notification
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>account/login.php" class="login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>account/register.php" class="signup">
                    <i class="fas fa-user-plus"></i> Signup
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>services/post-service.php" class="post-service">
                    <i class="fas fa-plus-circle"></i> Post a Service
                </a>
            </li>
            <!-- Profile Dropdown -->
            <li class="profile-dropdown">
                <a href="#" class="profile-image-link">
                    <img src="<?php echo BASE_URL; ?>images/profile-image.jpg" alt="Profile" class="profile-img">
                </a>
                <ul class="profile-dropdown-menu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>account/manage-account.php" >
                            <i class="fas fa-user"></i> Account
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-cogs"></i> Settings
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-chart-bar"></i> Ads Analysis
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>account/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</header>


