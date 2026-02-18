<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizDoc System Access Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .warning { color: #d97706; }
        .info { color: #0284c7; }
        h1 { color: #1e293b; margin-top: 0; }
        h2 { color: #334155; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; }
        .button { 
            display: inline-block;
            padding: 10px 20px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 4px;
        }
        .button:hover { background: #4338ca; }
        code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
        }
        .alert-success { background: #d1fae5; border-left: 4px solid #059669; }
        .alert-error { background: #fee2e2; border-left: 4px solid #dc2626; }
        .alert-warning { background: #fef3c7; border-left: 4px solid #d97706; }
        .alert-info { background: #dbeafe; border-left: 4px solid #0284c7; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 QuizDoc System Access Test</h1>
        <p>This page tests your access and shows exactly what you should see.</p>
    </div>

    <?php
    // Check if logged in
    $isLoggedIn = session('admin_authenticated', false);
    $userId = session('admin_user_id');
    $userRole = session('admin_role');
    
    if ($isLoggedIn && $userId) {
        $user = \App\Models\User::find($userId);
    } else {
        $user = null;
    }
    ?>

    @if(!$isLoggedIn || !$user)
        <div class="card alert-error">
            <h2 class="error">❌ NOT LOGGED IN</h2>
            <p><strong>Problem:</strong> You are not logged in. That's why you can't see the create buttons!</p>
            <p><strong>Solution:</strong></p>
            <ol>
                <li>Go to <code>/login</code></li>
                <li>Login with your coordinator account</li>
                <li>Come back to this page</li>
            </ol>
            <a href="/login" class="button">Go to Login Page</a>
        </div>
    @else
        <div class="card alert-success">
            <h2 class="success">✅ YOU ARE LOGGED IN</h2>
            <table>
                <tr>
                    <th>User ID</th>
                    <td>{{ $user->id }}</td>
                </tr>
                <tr>
                    <th>Username</th>
                    <td>{{ $user->username }}</td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $user->name ?? 'Not set' }}</td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td><strong>{{ $user->role }}</strong></td>
                </tr>
                <tr>
                    <th>Is Coordinator?</th>
                    <td>{{ $user->isDocuMentorCoordinator() ? '✅ YES' : '❌ NO' }}</td>
                </tr>
                <tr>
                    <th>Is Examiner?</th>
                    <td>{{ $user->isExaminer() ? '✅ YES' : '❌ NO' }}</td>
                </tr>
                <tr>
                    <th>Is Super Admin?</th>
                    <td>{{ $user->isSuperAdmin() ? '✅ YES' : '❌ NO' }}</td>
                </tr>
            </table>
        </div>

        @if($user->isDocuMentorCoordinator())
            <div class="card alert-success">
                <h2 class="success">✅ YOU HAVE COORDINATOR ACCESS</h2>
                <p>Perfect! You should be able to see all coordinator pages and create buttons.</p>
                
                <h3>🎯 Test These Pages (Click to Open):</h3>
                
                <div style="margin: 20px 0;">
                    <h4>1. Coordinator Dashboard</h4>
                    <a href="/docu-mentor/coordinators" class="button">Open Coordinator Dashboard</a>
                    <p style="margin-top: 8px;">Expected: See 10 management cards</p>
                </div>

                <div style="margin: 20px 0;">
                    <h4>2. Academic Years</h4>
                    <a href="/docu-mentor/coordinators/academic-years" class="button">Open Academic Years</a>
                    <p style="margin-top: 8px;">Expected: See table with <strong>"Add Academic Year"</strong> button (blue, top right)</p>
                </div>

                <div style="margin: 20px 0;">
                    <h4>3. Quiz Categories</h4>
                    <a href="/docu-mentor/coordinators/quiz-categories" class="button">Open Quiz Categories</a>
                    <p style="margin-top: 8px;">Expected: See table with <strong>"Add Category"</strong> button (blue, top right)</p>
                </div>

                <div style="margin: 20px 0;">
                    <h4>4. Semesters</h4>
                    <a href="/docu-mentor/coordinators/semesters" class="button">Open Semesters</a>
                    <p style="margin-top: 8px;">Expected: See table with <strong>"Add Semester"</strong> button (blue, top right)</p>
                </div>

                <div style="margin: 20px 0;">
                    <h4>5. Academic Classes</h4>
                    <a href="/docu-mentor/coordinators/academic-classes" class="button">Open Academic Classes</a>
                    <p style="margin-top: 8px;">Expected: See table with <strong>"Add Class"</strong> button (blue, top right)</p>
                </div>
            </div>

            <div class="card alert-info">
                <h2>🔧 Troubleshooting</h2>
                <h3>If you STILL don't see the create buttons after clicking the links above:</h3>
                <ol>
                    <li><strong>Clear Browser Cache:</strong> Press <code>Ctrl+Shift+R</code> (Windows) or <code>Cmd+Shift+R</code> (Mac)</li>
                    <li><strong>Check Browser Console:</strong> Press <code>F12</code> and look for JavaScript errors</li>
                    <li><strong>Try Different Browser:</strong> Open in Chrome/Firefox/Safari</li>
                    <li><strong>Check Screen Width:</strong> Make sure browser window is wide enough (buttons use flexbox)</li>
                </ol>
            </div>

        @else
            <div class="card alert-error">
                <h2 class="error">❌ YOU ARE NOT A COORDINATOR</h2>
                <p><strong>Current Role:</strong> <code>{{ $user->role }}</code></p>
                <p><strong>Problem:</strong> Your account doesn't have coordinator privileges.</p>
                
                <h3>✅ Solution: Update Your Role in Database</h3>
                <p>Run this SQL query to make yourself a coordinator:</p>
                <div style="background: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 8px; font-family: monospace; margin: 16px 0;">
UPDATE users<br>
SET role = 'coordinator'<br>
WHERE id = {{ $user->id }};<br>
<br>
-- Or make super_admin (has all access):<br>
UPDATE users<br>
SET role = 'super_admin'<br>
WHERE id = {{ $user->id }};
                </div>
                
                <p style="margin-top: 16px;">After running the query:</p>
                <ol>
                    <li>Logout: <code>/logout</code></li>
                    <li>Login again: <code>/login</code></li>
                    <li>Return to this page to verify</li>
                </ol>

                <form action="/logout" method="POST" style="margin-top: 16px;">
                    @csrf
                    <button type="submit" class="button" style="background: #dc2626;">Logout Now</button>
                </form>
            </div>
        @endif
    @endif

    <div class="card">
        <h2>📊 All Available Pages for Coordinators</h2>
        <table>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>URL</th>
                    <th>Create Button Text</th>
                    <th>Button Position</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Academic Years</td>
                    <td><code>/docu-mentor/coordinators/academic-years</code></td>
                    <td>"Add Academic Year"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Quiz Categories</td>
                    <td><code>/docu-mentor/coordinators/quiz-categories</code></td>
                    <td>"Add Category"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Semesters</td>
                    <td><code>/docu-mentor/coordinators/semesters</code></td>
                    <td>"Add Semester"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Academic Classes</td>
                    <td><code>/docu-mentor/coordinators/academic-classes</code></td>
                    <td>"Add Class"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Student Levels</td>
                    <td><code>/dashboard/student-levels</code></td>
                    <td>"Create Level"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Courses</td>
                    <td><code>/dashboard/courses</code></td>
                    <td>"Create Course"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Project Categories</td>
                    <td><code>/docu-mentor/coordinators/categories</code></td>
                    <td>"Add Category"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Project Groups</td>
                    <td><code>/docu-mentor/coordinators/groups</code></td>
                    <td>"Add Group"</td>
                    <td>Top right</td>
                </tr>
                <tr>
                    <td>Users</td>
                    <td><code>/docu-mentor/coordinators/users</code></td>
                    <td>"Add User"</td>
                    <td>Top right</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card alert-info">
        <h2>🔑 Key Points</h2>
        <ul>
            <li>✅ <strong>All logins use same page:</strong> <code>/login</code></li>
            <li>✅ <strong>No separate login for coordinators/examiners/admin</strong></li>
            <li>✅ <strong>Role determines access after login</strong></li>
            <li>✅ <strong>All create buttons ARE in the code (lines 9-11 of each index page)</strong></li>
            <li>✅ <strong>If you don't see buttons, issue is authentication/role, NOT missing code</strong></li>
        </ul>
    </div>

    <div class="card">
        <h2>🔍 Quick Database Check</h2>
        <p>Run these SQL queries to check your system:</p>
        
        <h3>1. Check All Users and Roles</h3>
        <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; font-family: monospace; margin: 8px 0;">
SELECT id, username, name, role FROM users ORDER BY role, username;
        </div>

        <h3>2. Count Coordinators</h3>
        <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; font-family: monospace; margin: 8px 0;">
SELECT COUNT(*) as coordinator_count FROM users WHERE role IN ('coordinator', 'super_admin');
        </div>

        <h3>3. Make User a Coordinator</h3>
        <div style="background: #f1f5f9; padding: 12px; border-radius: 6px; font-family: monospace; margin: 8px 0;">
UPDATE users SET role = 'coordinator' WHERE username = 'YOUR_USERNAME';
        </div>
    </div>

    <div class="card" style="background: #1e293b; color: #e2e8f0;">
        <h2 style="color: #e2e8f0; border-color: #475569;">✅ STATUS SUMMARY</h2>
        @if($isLoggedIn && $user && $user->isDocuMentorCoordinator())
            <p style="font-size: 24px; color: #10b981;">✅ ALL SYSTEMS READY</p>
            <p>You have full coordinator access. All create buttons should be visible.</p>
        @elseif($isLoggedIn && $user)
            <p style="font-size: 24px; color: #f59e0b;">⚠️ WRONG ROLE</p>
            <p>You're logged in but not as coordinator. Update your role in database.</p>
        @else
            <p style="font-size: 24px; color: #ef4444;">❌ NOT LOGGED IN</p>
            <p>Please login first at <code>/login</code></p>
        @endif
    </div>
</body>
</html>
