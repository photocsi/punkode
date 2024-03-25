<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

</head>

<body>

    <?php 
    require_once '../setup.php';
    require_once 'header.php';
    require_once 'offcanvas.html';

    ?>


    <main>
        <div class="container-fluid text-center" style="padding: 2rem">
            <div class="row align-items-start">
               
                <div class="col-10" style="text-align: left; border-right: 1px solid black ; padding-left: 3rem">


                    <a href="table.php">
                        <h5 class="text-primary">new TABLE_PK( string 'table')</h5>
                    </a>
                    </br>
                    <p>Stanziare la classe "new TABLE_PK" per visualizzare una tabella del database</p>
                    <p>La tabella conterrà già tutte le possibilità per modificare aggiungere ed eliminare i vari record</p>
                    </br></br>

                    <a href="int.php">
                        <h5 class="text-primary">input_number ( string 'label' , string 'name', array $options[] , string 'function_js')</h5>
                    </a>
                    </br>
                    <p>Crea un campo di input per numeri interi con i controlli di sicurezza sanitizzazione e validazione</p>
                    </br></br>


                </div>
                <div class="col-2" style="text-align: right; border-right: 1px solid black ; padding-left: 3rem">
                    <h3>PARAMETER USED</h3>
                    <h5 style="color:blue"><b>$table</b> string</h5>
                    <p>The name of the database table to work on</p>
                    <h5><b>$label</b> string</h5>
                    <p>l'etichetta del campo di input da visualizzare a schermo</p>
                    <h5><b>$name</b> string</h5>
                    <p>Il parametro name del campo input che risulterà nel POST es. $_POST['name']</p>
                    <h5><b>$where</b> string|array</h5>
                    <p>Il campo della tabella in cui applicare la condizione</p>
                    <h5><b>$value</b> string|array</h5>
                    <p>Il valore da confrontare con il where</p>
                    <h5 style="color:blue"><b>$table</b> string</h5>
                    <p>The name of the database table to work on</p>


                </div>
            </div>


        </div>

        </div> <!-- questi ultimi 3 div chiudono la sidebar -->
        </div>
        </div> <!-- ultimo div che chiude la side bar, da aggiungere al fondo di ogni pagina -->
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>


</body>

</html>