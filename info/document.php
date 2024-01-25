<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="asset\bootstrap-5.3.2-dist\css\bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <?php require_once '../setup.php';   ?>
    <nav class="navbar bg-body-tertiary">
        <form class="container-fluid justify-content-start">
            <a class="navbar-brand" href="#">
                <img src="/docs/5.3/assets/brand/bootstrap-logo.svg" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
                Bootstrap
            </a>
            <button class="btn btn-outline-success me-2" type="button">Main button</button>
            <button class="btn btn-sm btn-outline-secondary" type="button">Smaller button</button>
        </form>
    </nav>


    <main>
        <div class="container-fluid text-center" style="padding: 2rem">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Home</a></li>
                    <!-- <li class="breadcrumb-item"><a href="#">Library</a></li>
    <li class="breadcrumb-item active" aria-current="page">Data</li> -->
                </ol>
            </nav>
            <div class="row align-items-start">
                <div class="col-3" style="text-align: left; border-right: 1px solid grey ; padding-right: 3rem ">
                    <h3>START MODE</h3>
                    <p>require_once 'punkode/setup.php';</p>
                    <p>inserire la stringa require sopra in ogni pagina dove intendete utilizzare il punkode ,
                         l'indirizzo del require deve puntare alla pagina setup.php che si trova all'interno della cartella pankode</p>
                    <p><b>BOOTSTRAP</b></p>
                    <p>Facendo il require di setup.php all'interno di punkode avrete già a disposizione i CDN di bootstrap, potete andare a sostituire
                        i CDN all'interno della pagina setup nel caso bootstrap cambi i CDN
                    </p>

                    <p> All'interno di asset trovate comunque bootstrap installato nel caso non vogliate usare i CDN, la cartella del bootstrap verra aggiornata insieme
                        agli aggiornamenti del punkode
                    </p>


                    </br>
                    <h5>Classi gerarchia </h5>
                    <p><b>setup</b> class parent</p>
                    <p><b>db_pdo</b> extends setup</p>
                    <p><b>input</b> extends db_pdo</p>
                    <p><b>table</b> extends input</p>
                    </br><hr>
                    <p><b>tool</b> extends db_pdo</p>



                </div>
                <div class="col-6" style="text-align: left; border-right: 1px solid black ; padding-left: 3rem">


                    <h3 class="text-primary">new TABLE_PUNK( string 'table')</h3>
                    </br>
                    <p>Stanziare la classe "new TABLE_PUNK" per visualizzare una tabella del database</p>
                    <p>La tabella conterrà già tutte le possibilità per modificare aggiungere ed eliminare i vari record</p>
                    </br></br>
                    <h5><b>Parametri</b></h5>
                    </br>
                    <p><b>string 'table'</b> (obbligatorio) = Nome della tabella da visualizzare</p>
                    </br></br>
                    <h5><b>Considerazioni</b></h5>
                    </br>
                    <p><b>new TABLE_PUNK</b> e probabilmente lo strumento più potente di Punkode, permette con una sola riga di codice di avere a disposizione un'intera tabella
                        dove compiere tutte le operazioni per gestire i nostri dati. In pratica dopo aver creato le nostre tabelle abbiamo un gestionale a tutti gli effetti con una sola riga di codice.
                        la classe TABLE_PUNK estendendo a catena la classe input e db_pdo e setup è in grado di sfruttare tutta la potenza del punkode</p>

                </div>
                <div class="col-3">



                </div>
            </div>


        </div>

    </main>

    <script src="asset/bootstrap-5.3.2-dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>