<?php
/* =============================== *
 * TogetherA+  –  Helper Sign-up  *
 * =============================== */

/* ----------  DB CONNECTION ---------- */
$host     = 'localhost';
$dbName   = 'togetheraplus';
$username = 'root';
$password = '1234';     //  ← change if you use another MySQL password

$conn = new mysqli($host, $username, $password, $dbName);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

/* ----------  HANDLE FORM SUBMISSION ---------- */
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Grab & sanitise inputs
    $name     = trim($_POST['name']  ?? '');
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $skills   = trim($_POST['skills'] ?? '');
    $passwordPlain = $_POST['password'] ?? '';

    // 2. Basic validation
    if ($name === '')                        $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($passwordPlain === '')               $errors[] = 'Password is required.';
    if ($phone === '')                       $errors[] = 'Phone number is required.';
    if ($address === '')                     $errors[] = 'Address is required.';
    if ($skills === '')                      $errors[] = 'Skills field is required.';

    // 3. Check duplicate email
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT helper_id FROM helpers WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'That email is already registered.';
        }
        $stmt->close();
    }

    // 4. Handle upload (optional – directory: /uploads/skill_docs)
    $skillDocPath = null;
    if (empty($errors) &&
        isset($_FILES['skill_verification_doc']) &&
        $_FILES['skill_verification_doc']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = __DIR__ . '/uploads/skill_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $safeName = uniqid('', true) . '_' .
                    preg_replace('/[^a-zA-Z0-9\._-]/', '', $_FILES['skill_verification_doc']['name']);
        $target = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['skill_verification_doc']['tmp_name'], $target)) {
            $skillDocPath = 'uploads/skill_docs/' . $safeName;   //  (column not yet in DB – for future use)
        } else {
            $errors[] = 'File upload failed – please try again.';
        }
    }

    // 5. Insert helper record
    if (empty($errors)) {
        $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            'INSERT INTO helpers
                (name, email, password_hash, phone_number, address, skills)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $name, $email, $passwordHash, $phone, $address, $skills);

        if ($stmt->execute()) {
            $success = 'Registration successful! Your account will be activated after admin verification.';
            // Optionally: send admin email / redirect to login page here
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TogetherA+ HelpMates</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap"
      rel="stylesheet"
    />

    <style>
      /* Hero section background pseudo-element for the large shape */
      .hero-section::after {
        content: "";
        position: absolute;
        bottom: -150px;
        right: -250px;
        width: 800px;
        height: 600px;
        background-color: #0d1a3a; /* brand-dark color */
        border-radius: 50%;
        transform: rotate(15deg);
        z-index: 0;
      }

      /* --- SCROLL ANIMATION --- */
      .scroll-target {
        opacity: 0;
        transform: translateY(50px); /* Animate from bottom */
        transition: opacity 0.8s ease-out, transform 0.8s ease-out;
      }

      .scroll-target.visible {
        opacity: 1;
        transform: translateY(0);
      }

      /* Hero section title animation */
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .animate-fade-in-up {
        animation: fadeInUp 1s ease-out forwards;
      }

      /* Divider lines in stats section */
      .stat-item {
        position: relative;
        padding-top: 20px;
      }

      .stat-item::before {
        content: "";
        position: absolute;
        top: 0;
        left: 10%;
        right: 10%;
        height: 3px;
        background-color: #38bdf8; /* sky-400 */
      }

      /* --- Styles for Scrolled Navbar --- */
      #navbar-container nav {
        transition: background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        background-color: transparent;
      }

      #navbar-container nav.scrolled {
        background-color: rgba(255, 255, 255, 0.9);
        -webkit-backdrop-filter: blur(16px);
        backdrop-filter: blur(16px);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      }
    </style>

    <script>
      // Custom Tailwind theme configuration
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ["Inter", "sans-serif"],
            },
            colors: {
              "brand-dark": "#0d1a3a",
              "brand-light-blue": "#e0f2fe",
              "brand-button": "#4f46e5",
              "brand-pale-blue": "#f0faff",
            },
          },
        },
      };
    </script>
  </head>
  <body class="bg-white text-gray-800">
    <header id="navbar-container">
      <nav class="fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-6 py-3">
          <div class="flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-gray-900">
              <img
                src="img/logo2.png"
                alt="TogetherA+ Logo"
                class="h-8 w-auto"
                onerror="this.onerror=null;this.src='https://placehold.co/150x50/e0f2fe/333?text=Logo';"
              />
            </a>
            <div class="hidden md:flex items-center space-x-6">
  
              <a
                href="login.php"
                class="bg-indigo-600 text-white font-semibold py-2 px-5 rounded-full hover:bg-indigo-700 transition-colors"
                >Login</a
              >
            </div>
            <div class="md:hidden">
              <button id="mobile-menu-button" class="text-gray-800 p-2">
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16m-7 6h7"
                  ></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg">
          <a
            href="#"
            class="block py-3 px-6 text-sm text-gray-700 hover:bg-gray-100"
            >Resources</a
          >
          <a
            href="login.php"
            class="block py-3 px-6 text-sm text-gray-700 hover:bg-gray-100"
            >Login</a
          >
        </div>
      </nav>
    </header>

    <main>
      <section
        class="hero-section bg-brand-light-blue pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden relative"
      >
        <div class="container mx-auto px-6 relative z-10">
          <div class="grid md:grid-cols-2 gap-8 items-center">
            <div class="text-left animate-fade-in-up">
              <h1
                class="text-4xl md:text-6xl font-black text-gray-900 leading-tight"
              >
                Lend Support <br />Earn with Purpose
              </h1>
              <p class="mt-4 text-lg text-gray-600 max-w-md">
                Join TogetherA+ and discover a rewarding way to use your skills.
                Connect with individuals with disabilities, provide essential
                support, and help build a more inclusive community—all while
                earning on your own schedule.
              </p>
            </div>
            
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-2xl">
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700">
                        <ul class="list-disc list-inside text-sm">
                            <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                        </ul>
                    </div>
                <?php elseif ($success): ?>
                    <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 text-sm">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <h3 class="text-2xl font-bold text-center text-gray-900 mb-4">Become a HelpMate Today</h3>
                
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="name" placeholder="Full Name" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <input type="email" name="email" placeholder="Email Address" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <input type="text" name="phone" placeholder="Phone Number" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <input type="text" name="address" placeholder="Address" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <input type="text" name="skills" placeholder="Skills (e.g., Tutoring, Cleaning)" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <div>
                        <label for="skill_verification_doc" class="sr-only">Skill Verification Document</label>
                        <input type="file" name="skill_verification_doc" id="skill_verification_doc" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <input type="password" name="password" placeholder="Password" required class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent rounded-full shadow-lg text-base font-semibold text-white bg-indigo-700 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Apply Now
                    </button>
                </form>
            </div>

          </div>
        </div>
      </section>

      <section
        class="stats-section bg-brand-dark text-white py-20 md:py-28 scroll-target"
      >
        <div class="container mx-auto px-6 text-center">
          <h2 class="text-3xl md:text-4xl font-bold mb-12">
            Where we are today
          </h2>
          <div
            class="grid grid-cols-2 md:grid-cols-5 gap-8 max-w-6xl mx-auto"
          >
            <div class="stat-item">
              <p class="text-4xl md:text-5xl font-bold text-sky-400">250+</p>
              <p class="text-sm text-gray-400 mt-2">
                Individuals & HelpMates Connected
              </p>
            </div>
            <div class="stat-item">
              <p class="text-4xl md:text-5xl font-bold text-sky-400">1,000+</p>
              <p class="text-sm text-gray-400 mt-2">
                Hours of Support Facilitated
              </p>
            </div>
            <div class="stat-item">
              <p class="text-4xl md:text-5xl font-bold text-sky-400">50+</p>
              <p class="text-sm text-gray-400 mt-2">
                Educational & Skill-Based Tasks Completed
              </p>
            </div>
            <div class="stat-item">
              <p class="text-4xl md:text-5xl font-bold text-sky-400">5+</p>
              <p class="text-sm text-gray-400 mt-2">
                Divisions Across Bangladesh Served
              </p>
            </div>
            <div class="stat-item col-span-2 md:col-span-1">
              <p class="text-4xl md:text-5xl font-bold text-sky-400">100%</p>
              <p class="text-sm text-gray-400 mt-2">
                Verified & Vetted HelpMates
              </p>
            </div>
          </div>
          <div class="mt-16 flex justify-center">
            <img
              src="img/bdmap.gif"
              alt="Map of Bangladesh"
              class="opacity-50 max-w-md w-full"
              onerror="this.onerror=null;this.src='https://placehold.co/800x400/0d1a3a/ffffff?text=Image+Not+Found';"
            />
          </div>
        </div>
      </section>

      <section class="bg-brand-pale-blue py-20 md:py-28 overflow-hidden">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
                        How It Works
                    </h2>
                    <p class="text-lg text-gray-600 mt-4 max-w-3xl mx-auto">
                        Our process is designed to be simple and transparent. Here’s your path to making an impact as a HelpMate on TogetherA+.
                    </p>
                </div>

                <div class="relative max-w-2xl mx-auto">
                    <div class="absolute left-4 md:left-9 top-9 h-full w-0.5 bg-sky-200" aria-hidden="true"></div>

                    <div class="relative scroll-target">
                        <div class="md:flex items-center">
                            <div class="flex-shrink-0 w-20 h-20 flex items-center justify-center">
                               <div class="bg-sky-500 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ring-8 ring-brand-pale-blue">1</div>
                            </div>
                            <div class="bg-white rounded-xl shadow-md p-6 ml-4 md:ml-8 flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Sign Up & Build Your Profile</h3>
                                <p class="text-gray-600">Create your secure account and build a profile that showcases your unique skills and availability. This helps us match you with the right opportunities.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative scroll-target mt-12">
                         <div class="md:flex items-center">
                            <div class="flex-shrink-0 w-20 h-20 flex items-center justify-center">
                               <div class="bg-sky-500 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ring-8 ring-brand-pale-blue">2</div>
                            </div>
                            <div class="bg-white rounded-xl shadow-md p-6 ml-4 md:ml-8 flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Get Matched with Tasks</h3>
                                <p class="text-gray-600">Once verified, you'll get notifications for tasks that match your skills. Review the details and accept the requests that work for you.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative scroll-target mt-12">
                         <div class="md:flex items-center">
                            <div class="flex-shrink-0 w-20 h-20 flex items-center justify-center">
                               <div class="bg-sky-500 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ring-8 ring-brand-pale-blue">3</div>
                            </div>
                            <div class="bg-white rounded-xl shadow-md p-6 ml-4 md:ml-8 flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Provide Support & Log Hours</h3>
                                <p class="text-gray-600">Connect with the user and provide the requested assistance. Simply log your hours transparently through the platform once the task is complete.</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative scroll-target mt-12">
                         <div class="md:flex items-center">
                            <div class="flex-shrink-0 w-20 h-20 flex items-center justify-center">
                               <div class="bg-sky-500 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg ring-8 ring-brand-pale-blue">4</div>
                            </div>
                            <div class="bg-white rounded-xl shadow-md p-6 ml-4 md:ml-8 flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">Receive Secure Payment</h3>
                                <p class="text-gray-600">Once the user confirms the logged hours, your payment is processed securely. Our system ensures you are always compensated fairly and promptly for your work.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

      <section
        class="life-section bg-white py-20 md:py-28 text-center scroll-target"
      >
        <div class="container mx-auto px-6">
          <h2 class="text-3xl md:text-4xl font-bold mb-12">
            Benefits of Being a HelpMate
          </h2>
          <div class="grid md:grid-cols-3 gap-12 max-w-4xl mx-auto mb-16">
            <div class="flex flex-col items-center">
              <div class="bg-sky-100 p-5 rounded-xl mb-4">
                <svg
                  class="w-8 h-8 text-sky-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                  ></path>
                </svg>
              </div>
              <h3 class="font-bold text-lg">Fair & Secure Payments</h3>
            </div>

            <div class="flex flex-col items-center">
              <div class="bg-sky-100 p-5 rounded-xl mb-4">
                <svg
                  class="w-8 h-8 text-sky-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  ></path>
                </svg>
              </div>
              <h3 class="font-bold text-lg">Flexible Hours</h3>
            </div>
            <div class="flex flex-col items-center">
              <div class="bg-sky-100 p-5 rounded-xl mb-4">
                <svg
                  class="w-8 h-8 text-sky-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  ></path>
                </svg>
              </div>
              <h3 class="font-bold text-lg">Anyone Can Apply</h3>
            </div>
          </div>
        </div>
      </section>

      <section class="impact-section bg-white py-20 md:py-28 scroll-target">
        <div class="container mx-auto px-6">
          <div
            class="grid md:grid-cols-2 gap-12 md:gap-16 items-center max-w-6xl mx-auto"
          >
            <div class="text-left">
              <h2
                class="text-4xl md:text-5xl font-black text-gray-900 leading-tight"
              >
                Your Support Changes Lives
              </h2>
              <p class="mt-4 text-lg text-gray-600 max-w-md">
                At TogetherA+, every task you complete is a step towards a more
                inclusive Bangladesh. You’re not just providing assistance; you
                are unlocking potential, fostering independence, and helping
                individuals with disabilities access education and participate
                more fully in their communities.
              </p>
            </div>

            <div class="flex justify-center">
              <img
                src="https://placehold.co/500x400/e0f2fe/1e3a8a?text=Impact+Illustration"
                alt="Illustration showing a helper assisting an individual with a book, symbolizing educational support."
                class="w-full max-w-md h-auto rounded-lg"
                onerror="this.onerror=null;this.src='https://placehold.co/500x400/ffffff/333?text=Image+Not+Found';"
              />
            </div>
          </div>
        </div>
      </section>

      <section class="team-goals-section bg-gray-100 py-20 md:py-28 scroll-target">
        <div class="container mx-auto px-6">
          <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-center max-w-6xl mx-auto">
            <div class="text-center md:text-left order-2 md:order-1">
              <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                Our Team's Goals and Motivation
              </h2>
              <p class="text-gray-600 leading-relaxed mb-4">
                We are a collective of passionate individuals dedicated to bridging gaps in our community. Our primary goal is to empower individuals with disabilities by connecting them with the skilled, reliable support they need to thrive academically, professionally, and personally.
              </p>
              <p class="text-gray-600 leading-relaxed mb-6">
                Our motivation stems from a deeply rooted belief in equality and the potential within every person. We're not just building a platform; we're fostering a movement of empathy, support, and shared success across Bangladesh, one connection at a time.
              </p>
            </div>
            <div class="order-1 md:order-2 flex justify-center">
                <div class="grid grid-cols-6 gap-2 w-full max-w-md">
                    <img src="img/leader1.jpg" alt="Team Leader 1" class="col-span-3 aspect-square object-cover w-full h-full rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/200x200/cccccc/333?text=Leader+1';">
                    <img src="img/leader2.jpg" alt="Team Leader 2" class="col-span-3 aspect-square object-cover w-full h-full rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/200x200/cccccc/333?text=Leader+2';">
                    <img src="img/leader3.jpg" alt="Team Leader 3" class="col-span-2 aspect-square object-cover w-full h-full rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/200x200/cccccc/333?text=Leader+3';">
                    <img src="img/leader4.jpg" alt="Team Leader 4" class="col-span-2 aspect-square object-cover w-full h-full rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/200x200/cccccc/333?text=Leader+4';">
                    <img src="img/leader5.jpg" alt="Team Leader 5" class="col-span-2 aspect-square object-cover w-full h-full rounded-lg shadow-lg" onerror="this.onerror=null;this.src='https://placehold.co/200x200/cccccc/333?text=Leader+5';">
                </div>
            </div>
          </div>
        </div>
      </section>


      <section class="bg-brand-pale-blue py-20 md:py-28 overflow-hidden">
        <div class="container mx-auto px-6">
          <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
              What our HelpMates are saying
            </h2>
          </div>
          <div id="testimonials-carousel-container" class="relative max-w-3xl mx-auto">
            <div id="testimonials-carousel" class="flex transition-transform duration-700 ease-in-out">
              </div>
            <button id="prev-testimonial" class="absolute top-1/2 -left-4 md:-left-12 transform -translate-y-1/2 bg-white/80 rounded-full p-2 shadow-md hover:bg-white transition focus:outline-none">
              <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button id="next-testimonial" class="absolute top-1/2 -right-4 md:-right-12 transform -translate-y-1/2 bg-white/80 rounded-full p-2 shadow-md hover:bg-white transition focus:outline-none">
              <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
          </div>
        </div>
      </section>

    </main>

    <footer class="bg-brand-dark text-white">
        <div class="container mx-auto py-8 px-6 text-center">
            <p>&copy; 2024 TogetherA+. All Rights Reserved.</p>
            <div class="mt-4">
                <a href="#" class="text-gray-400 hover:text-white mx-2">Privacy Policy</a>
                <span class="text-gray-500">|</span>
                <a href="#" class="text-gray-400 hover:text-white mx-2">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // --- Navbar Logic ---
        const navElement = document.querySelector("#navbar-container nav");
        const mobileMenuButton = document.getElementById("mobile-menu-button");
        const mobileMenu = document.getElementById("mobile-menu");

        if (mobileMenuButton && mobileMenu) {
          mobileMenuButton.addEventListener("click", () => {
            mobileMenu.classList.toggle("hidden");
          });
        }

        if (navElement) {
          window.addEventListener("scroll", () => {
            navElement.classList.toggle("scrolled", window.scrollY > 10);
          });
        }

        // --- Scroll Animation for Page Sections ---
        const scrollTargets = document.querySelectorAll(".scroll-target");
        const scrollObserver = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                scrollObserver.unobserve(entry.target);
              }
            });
          },
          {
            root: null,
            threshold: 0.2,
          }
        );
        scrollTargets.forEach((target) => scrollObserver.observe(target));
        
        // --- Testimonials Carousel Logic ---
        const testimonials = [
            {
                quote: "Working through TogetherA+ has been exceptionally rewarding. It provides ample opportunities for personal growth and making a real difference. I particularly appreciate the flexibility and the direct connection with those I'm helping.",
                name: "Christine Presto",
                title: "HelpMate",
                avatar: "https://placehold.co/40x40/E2E8F0/4A5568?text=CP"
            },
            {
                quote: "The platform is incredibly user-friendly, and the support team is always responsive. It feels great to be part of a community that genuinely cares about both the helpers and the individuals receiving support.",
                name: "David Chen",
                title: "HelpMate",
                avatar: "https://placehold.co/40x40/E2E8F0/4A5568?text=DC"
            },
            {
                quote: "I've been a HelpMate for over a year, and it's been a fantastic experience. The payment system is reliable, and I love being able to choose tasks that fit my schedule and skill set. Highly recommended!",
                name: "Aisha Khan",
                title: "HelpMate",
                avatar: "https://placehold.co/40x40/E2E8F0/4A5568?text=AK"
            },
            {
                quote: "As a student, this has been the perfect way to earn an income while contributing positively. The tasks are meaningful, and I've learned so much from the people I've had the privilege to assist.",
                name: "Samuel B.",
                title: "HelpMate",
                avatar: "https://placehold.co/40x40/E2E8F0/4A5568?text=SB"
            }
        ];

        const carousel = document.getElementById('testimonials-carousel');
        const prevButton = document.getElementById('prev-testimonial');
        const nextButton = document.getElementById('next-testimonial');
        const carouselContainer = document.getElementById('testimonials-carousel-container');
        let currentIndex = 0;
        let intervalId;

        function renderCarousel() {
            if (!carousel) return;
            carousel.innerHTML = testimonials.map(t => `
                <div class="flex-shrink-0 w-full px-2">
                    <div class="bg-sky-100/50 rounded-2xl p-8 text-left h-full">
                        <p class="text-gray-700 italic">"${t.quote}"</p>
                        <div class="flex items-center mt-6">
                            <img src="${t.avatar}" alt="${t.name}" class="w-12 h-12 rounded-full mr-4"/>
                            <div>
                                <div class="font-bold">${t.name}</div>
                                <div class="text-sm text-gray-600">${t.title}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function showCard(index) {
            if (!carousel) return;
            const offset = -index * 100;
            carousel.style.transform = `translateX(${offset}%)`;
        }
        
        function nextCard() {
            currentIndex = (currentIndex + 1) % testimonials.length;
            showCard(currentIndex);
        }

        function prevCard() {
            currentIndex = (currentIndex - 1 + testimonials.length) % testimonials.length;
            showCard(currentIndex);
        }

        function startAutoplay() {
            stopAutoplay(); // Clear any existing interval
            intervalId = setInterval(nextCard, 5000); // Change slide every 5 seconds
        }

        function stopAutoplay() {
            clearInterval(intervalId);
        }

        if(carouselContainer) {
            renderCarousel();
            nextButton.addEventListener('click', () => {
                nextCard();
                stopAutoplay();
                startAutoplay();
            });
            prevButton.addEventListener('click', () => {
                prevCard();
                stopAutoplay();
                startAutoplay();
            });
            carouselContainer.addEventListener('mouseenter', stopAutoplay);
            carouselContainer.addEventListener('mouseleave', startAutoplay);
            startAutoplay();
        }
      });
    </script>
  </body>
</html>