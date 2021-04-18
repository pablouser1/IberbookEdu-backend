<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users - IberbookEdu</title>
    <script defer src="https://use.fontawesome.com/releases/v5.15.1/js/all.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
    <script>
    const groups = {!! json_encode($groups) !!}
    </script>
</head>

<body>
    <section class="hero is-primary">
        <div class="hero-body">
            <div class="container">
                <h1 class="title">
                    User administration
                </h1>
                <h2 class="subtitle">
                    Add/remove users
                </h2>
            </div>
        </div>
    </section>
    <section id="option" class="section tab">
        <div class="container has-text-centered">
            <p class="title">Choose an action</p>
            <div class="buttons is-centered">
                <a href="#add" class="button is-link">
                    <span class="icon">
                        <i class="fas fa-user-friends"></i>
                    </span>
                    <span>Add user(s)</span>
                </a>
                <a href="#remove" class="button is-link">
                    <span class="icon">
                        <i class="fas fa-id-card"></i>
                    </span>
                    <span>Remove user(s)</span>
                </a>
            </div>
        </div>
    </section>
    <section id="add" class="section is-hidden tab">
        <section class="section">
            <div class="control has-text-centered">
                <button onclick="addUserCard()" type="button" class="button is-info">
                    <span class="icon">
                        <i class="fas fa-user-friends"></i>
                    </span>
                    <span>Append new</span>
                </button>
            </div>
            <form id="add_form" autocomplete="off">
                <div id="add_columns" class="columns is-multiline"></div>
                <button type="submit" class="button is-success">Add</button>
            </form>
        </section>
        <hr>
        <section class="section">
            <p class="title">Import</p>
            <p>You can also import a JSON file</p>
            <div class="file has-name is-boxed">
                <label class="file-label">
                    <input id="jsoninput" class="file-input" type="file" name="json" accept="application/json">
                    <span class="file-cta">
                        <span class="file-icon">
                            <i class="fas fa-upload"></i>
                        </span>
                        <span class="file-label">Choose a file…</span>
                    </span>
                    <span id="jsonname" class="file-name"></span>
                </label>
            </div>
            <button id="jsonsend" type="button" class="button is-success">Send</button>
        </section>
    </section>
    <section id="remove" class="section is-hidden tab">
        <form id="remove_form">
            <div class="field">
                <label class="label">Select users</label>
                <div class="control">
                    <div id="select_container" class="select is-multiple">
                        <select id="remove_select" name="users[]" multiple>
                            @foreach ($users as $tempUser)
                                <option value="{{ $tempUser->id }}">{{ $tempUser->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="button is-danger">Delete</button>
        </form>
    </section>
    <footer class="footer">
        <nav class="breadcrumb is-centered" aria-label="breadcrumbs">
            <ul>
                <li>
                    <a href="#">
                        <span class="icon is-small">
                            <i class="fas fa-undo"></i>
                        </span>
                        <span>Back to main menu</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard">
                        <span class="icon is-small">
                            <i class="fas fa-columns" aria_hidden="true"></i>
                        </span>
                        <span>Back to control panel</span>
                    </a>
                </li>
            </ul>
        </nav>
    </footer>
    <script src="../../storage/resources/js/users.js"></script>
</body>

</html>
