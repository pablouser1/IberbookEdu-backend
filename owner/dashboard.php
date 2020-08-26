<?php
session_start();
require_once("../helpers/db.php");
if (!isset($_SESSION["owner"])){
    header("Location: login.php");
}

$ownerinfo = $_SESSION["ownerinfo"];
$stmt = $conn->prepare("SELECT id, username, permissions FROM staff");
$stmt->execute();
$result = $stmt->get_result();
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row["id"];
    $staff[$id] = array();
    foreach ($row as $value) {
        $staff[$id][] = $value;
    }
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard dueño</title>
    <script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">
  </head>

  <body>
    <section class="hero is-primary">
        <div class="hero-body">
          <div class="container">
            <h1 class="title">
              Bienvenido, <?php echo($ownerinfo["username"]);?>
            </h1>
            <h2 class="subtitle">
              Panel de control del dueño
            </h2>
          </div>
        </div>
    </section>
    <section class="section">
        <div class="columns">
            <div class="column">
                <p class="title">
                    <i class="fas fa-user-shield"></i>
                    <span>Staff</span>
                </p>
                <table class="table is-bordered is-striped is-hoverable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de usuario</th>
                        <th>Permisos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($staff as $user){
                        echo <<<EOL
                        <tr>
                        <td>$user[0]</td>
                        <td>$user[1]</td>
                        <td>$user[2]</td>
                        </tr>
                        EOL;
                    }
                    ?>
                </tbody>
                </table>
            </div>
        </div>
        <div class="buttons">
            <button id="managestaff" type="button" class="button is-info">
                <span class="icon">
                    <i class="fas fa-user-friends"></i>
                </span>
                <span>Agregar/eliminar staff</span>
            </button>
        </div>
        <hr>
        <p class="title">
            <span class="icon">
                <i class="fas fa-tasks"></i>
            </span>
            <span>Administración general</span>
        </p>
        <div class="buttons">
            <button id="manageschool" type="button" class="button is-info">
                <span class="icon">
                    <i class="fas fa-school"></i>
                </span>
                <span>Agregar/eliminar centro</span>
            </button>  
            <button id="archive" type="button" class="button is-danger">
                <span class="icon">
                    <i class="fas fa-archive"></i>
                </span>
                <span>Archivar</span>
            </button>  
        </div>
    </section>
    <!-- Modal for adding/removing staff members -->
    <div id="modalstaff" class="modal">
        <div onclick="closestaff()" class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Agregar/eliminar staff</p>
                <button onclick="closestaff()" class="delete" aria-label="close"></button>
            </header>
            <form action="managestaff.php" method="POST">
                <section class="modal-card-body">
                    <div class="field">
                        <label class="label">Nombre de usuario</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" name="username" placeholder="usuario" required>
                            <span class="icon is-left">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                    </div>
                    <div id="password" class="field is-hidden">
                        <label class="label">Contraseña</label>
                        <div class="control has-icons-left">
                            <input class="input" type="password" name="password" placeholder="***********">
                            <span class="icon is-left">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">Tipo de usuario</label>
                        <div class="control">
                            <div class="select">
                                <select id="typestaff">
                                    <option value="admin">Administrador</option>
                                    <option value="owner">Dueño</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <button id="addstaff" name="addstaff" value="admin" class="button is-success" type="submit">
                        <span class="icon">
                            <i class="fas fa-plus"></i>
                        </span>
                        <span>Agregar</span>
                    </button>
                    <button id="removestaff" name="removestaff" value="admin" class="button is-danger" type="submit">
                        <span class="icon">
                            <i class="fas fa-minus"></i>
                        </span>
                        <span>Eliminar</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
    <!-- Modal for adding/removing schools -->
    <div id="modalschool" class="modal">
        <div onclick="closeschool()" class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Agregar/eliminar staff</p>
                <button onclick="closeschool()" class="delete" aria-label="close"></button>
            </header>
            <form action="manageschool.php" method="POST">
                <section class="modal-card-body">
                    <div class="field">
                        <label class="label">Código del centro</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" name="id" placeholder="124363123" required>
                            <span class="icon is-left">
                                <i class="fas fa-school"></i>
                            </span>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">Nombre del centro (Sólo necesario si estás <b>agregando</b> un centro)</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" name="schoolname" placeholder="I.E.S Pepito de los Palotes">
                            <span class="icon is-left">
                                <i class="fas fa-school"></i>
                            </span>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <button name="addschool" class="button is-success" type="submit">
                        <span class="icon">
                            <i class="fas fa-plus"></i>
                        </span>
                        <span>Agregar</span>
                    </button>
                    <button name="removeschool" class="button is-danger" type="submit">
                        <span class="icon">
                            <i class="fas fa-minus"></i>
                        </span>
                        <span>Eliminar</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
    <!-- Modal for deleting rows of specific school -->
    <div id="modalarchive" class="modal">
        <div onclick="closearchive()" class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Archivar datos</p>
                <button onclick="closearchive()" class="delete" aria-label="close"></button>
            </header>
            <form action="archive.php" method="POST">
                <section class="modal-card-body">
                    <p>Archiva los yearbooks de un centro</p>
                    <p>
                        <span class="has-background-danger"><strong>ADVERTENCIA</strong></span>,
                        esta función sólo conserva los .zip de los yearbooks, tanto los datos de la base de datos como los archivos
                        subidos por los usuarios serán <strong>borrados.</strong><br>
                        <span class="has-background-danger"><u>ESTA ACCIÓN ES IRREVERSIBLE</u></span>
                    </p>
                    <div class="field">
                        <label class="label">Código del centro</label>
                        <div class="control has-icons-left">
                            <input class="input" type="text" name="id" placeholder="124363123" required>
                            <span class="icon is-left">
                                <i class="fas fa-school"></i>
                            </span>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <button name="archive" class="button is-danger" type="submit">
                        <span class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <span>Limpiar</span>
                    </button>
                </footer>
            </form>
        </div>
    </div>
    <footer class="footer">
        <nav class="breadcrumb is-centered" aria-label="breadcrumbs">
            <ul>
                <li>
                    <a href="../logout.php">
                        <span class="icon is-small">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        </span>
                        <span>Cerrar sesión</span>
                    </a>
                </li>
            </ul>
        </nav>
    </footer>
    <script src="scripts/dashboard.js"></script>
  </body>

</html>
