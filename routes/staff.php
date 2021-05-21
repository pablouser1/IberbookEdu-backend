<?php

use Helpers\Auth;
use Helpers\Misc;
use Models\Gallery;
use Models\Group;
use Models\Profile;
use Models\School;
use Models\Staff;
use Models\Theme;
use Models\User;

/**@var Leaf\App $app */

$app->group('/staff', function() use ($app) {
    $app->post("/", "StaffController@create");
    $app->delete("/(\d+)", "StaffController@delete");
});

$app->get("/staff/login", function () {
    $owner = Auth::isStaffLoggedin(false);
    if ($owner) {
        response()->redirect("owner/dashboard");
    }
    else {
        render("staff/login");
    }
});

$app->post("/staff/login", "AccountController@staff");

$app->group("/staff/owner", function () use($app) {
    $app->get("/dashboard", function () {
        $user = Auth::isStaffLoggedin();

        // Staff
        $staff = Staff::all();

        // Schools
        $schools = School::all();

        // Groups
        $groups = Group::all();

        // Themes
        $themes = Theme::all();

        render("staff/owner/dashboard",
        [
            "user" => $user,
            "schools" => $schools,
            "staff" => $staff,
            "groups" => $groups,
            "themes" => $themes
        ]);
    });

    $app->get("/users", function () {
        $user = Auth::isStaffLoggedin();

        // Staff
        $users = User::all();
        $profiles = Profile::all();
        $groups = json_encode(Group::all());

        render("staff/owner/users",
        [
            "user" => $user,
            "users" => $users,
            "profiles" => $profiles,
            "groups" => $groups
        ]);
    });

    $app->delete("/clear", function() {
        $owner = Auth::isStaffLoggedin();
        $password = requestData("password");
        if ($password) {
            $owner->makeVisible(['password']);
            // Valid password, delete
            if (password_verify($password, $owner->password)) {
                $uploadsDir = app_paths("uploads_path");
                $dirs = glob($uploadsDir . "/*", GLOB_ONLYDIR);
                foreach ($dirs as $dir) {
                    Misc::recursiveRemove($dir);
                }
                User::truncate();
                Profile::truncate();
                Gallery::truncate();
                response("Reseted successfully");
            }
        }
    });
});
