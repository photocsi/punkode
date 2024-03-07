<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>Document</title>
</head>

<body>

  <?php  require_once 'header-document.php';  
  require_once 'header-document.php'; 
    use Punkode as pk; ?>
  <main>
        <div class="container-fluid text-center" style="padding: 5rem">
            <div class="row align-items-start">
                <div class="col-6" style="text-align: left; border-right: 1px solid grey ; padding-right: 3rem ">
                <h4>IT</h4>
                    <h5><b style="color: #164d09">table_pk</b> (
                        <i class="text-secondary">string</i> <b class="text-primary">$table</b>)
                    </h5>
                    </br>
                    <p>Crea una tabella già impaginata con tutte le funzionalità di modifica, aggiungi ed elimina campo i più la possibilita di modificare le colonne del db,
                         in futuro sarà possibile scegliere anche lo stile della tabella.</p>
                    </br></br>
                    <h5><b>Parametri</b></h5>
                    </br>
                    <p><b class="text-primary">$table</b> <i class="text-secondary">string (obbligatorio)</i> = Nome della tabella da mostrare</p>
                    
                    <h5><b>Considerazioni</b></h5>
                    </br>
                    <p>Indubbiamente per ora lo strumento più potente di PunKode con una sola riga di codice avete a disposizione tutti gli strumenti necessari per lavorare su una tabella del database
                        , tabella responsive da posizionare nel vostro sito dove volete, da utilizzare come un gestionale già pronto e con le misure di sicurezza come sanitizzazione , validazione che verranno ampliate sempre di più
                        per rendere la vostra tabella sempre meno attaccabile.

                    </p>

                    <h5><b>Esempi</b></h5>

                    <div class="card text-start">
                        <div class="card-header">
                            C'e' poco da spiegare, basta instaziare la classe e come parametro inserire il nome della tabella da visualizzare
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <b>new TABLE_PK('nome_tabella');
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new PK\INPUT_PK();
                            $input->int_pk('anni');
                            ?>
                        </div>
                    </div>
                    <br>
                    <div class="card text-start">
                        <div class="card-header">
                            Campo input numerico, avrà come label 'anni', come name 'post_anni', sarà un campo richiesto obbligatorio dal form , dimensione small e occuperà un terzo dello spazio orizzontale
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <b>$input = new INPUT_PK();
                                    </br> $input->int_pk('anni','post_anni','required',array('s',4));</b>
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new PK\INPUT_PK();
                            $input->int_pk('anni', 'post_anni', 'required', array('s', 4));
                            ?>
                        </div>
                    </div>

                    <br>
                    <div class="card text-start">
                        <div class="card-header">
                            Codice completo del form con un campo input numerico int
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <b>$input = new INPUT_PK('#',4); </b>
                                    </br> <b>$input->form_pk();</b> <i style='color:#164d09'> // la action del form punta alla stessa pagina e ha una classe bootstrap 'row gx-3 gy-2 align-items-center'</i>
                                    </br><b> $input->int_pk('anni','','required');</b> <i style='color:#164d09'> // l'input ha come label 'anni' e come name 'anni' accetta valori numerici int e il campo è obbligatorio</i>
                                    </br><b> $input->submit_pk('submit','invia');</b> <i style='color:#164d09'> // il submit ha come label 'submit' e come name 'invia' in automatico e di dimensione media colore primary</i>
                                    </br><b> $input->end_form_pk();</b>
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new PK\INPUT_PK();
                            $input->form_pk('#',4);
                            $input->int_pk('anni', '', 'required');
                            $input->submit_pk('submit', 'invia');
                            $input->end_form_pk();


                            ?>
                        </div>
                    </div>


                </div>

                <div class="col-6" style="text-align: left ; padding-right: 3rem ">

                </div>
            </div>
        </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>

</html>