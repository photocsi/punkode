<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>Document</title>
</head>

<body>
    <?php require_once '../setup.php';
    require_once 'header-document.php';  ?>
    <main>
        <div class="container-fluid text-center" style="padding: 5rem">
            <div class="row align-items-start">
                <div class="col-8" style="text-align: left; border-right: 1px solid grey ; padding-right: 3rem ">
                    <h5><b style="color: #164d09">int_pk</b> (
                        <i class="text-secondary">string</i> <b class="text-primary">$label</b>,
                        <i class="text-secondary">string</i> <b class="text-primary">$name</b>,
                        <i class="text-secondary">string</i> <b class="text-primary">$param</b>,
                        <i class="text-secondary">array</i> <b class="text-primary">$options[]</b>

                        )
                    </h5>
                    </br>
                    <p>Crea un campo di input per numeri interi con i controlli di sicurezza sanitizzazione e validazione</p>
                    </br></br>
                    <h5><b>Parametri</b></h5>
                    </br>
                    <p><b class="text-primary">$label</b> <i class="text-secondary">string (obbligatorio)</i> = Etichetta dell'input da visualizzare a schermo</p>
                    <p><b class="text-primary">$name</b> <i class="text-secondary">string (opzionale)</i> = Rappresenta l'attributo name dell'Html, riferimento per l'invio dei dati POST o GET:
                        <br>Se lasciato libero prenderà il valore di $label
                    </p>
                    <p><b class="text-primary">$param</b> <i class="text-secondary">string (opzionale)</i> = Tutti i parametri aggiuntivi che vogliamo inserire nell'input<br>
                        es: valori html come hidden o required oppure funzioni js come onclick=func() ecc. </p>
                    <p><b class="text-primary">$option[]</b> <i class="text-secondary">array (opzionale)</i> = Varie opzioni di stile frontend:
                        </br>primo parametro altezza del campo input 'l' Large , 'm' Medium , 's' small.
                        </br> secondo parametro larghezza del campo input da 1 a 12.</p>

                    <h5><b>Esempi</b></h5>

                    <div class="card text-start">
                        <div class="card-header">
                            Campo input numerico, l'attributo name sarà uguale al label quindi 'anni' , avrà una dimensione media e occuperà l'intero spazio orizzontale
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <b>$input = new INPUT_PK();
                                    </br> $input->int_pk('anni');</b>
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new INPUT_PK();
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
                            <?php $input = new INPUT_PK();
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
                                <b>$input = new INPUT_PK();
                                    </br> $input->form_pk();
                                    </br> $input->int_pk('anni','','required');
                                    </br> $input->submit_pk('submit','invia');
                                    </br> $input->end_form_pk();</b>
                            </p>
                        </div>
                        <div class="card-footer text-body-secondary">
                            <?php $input = new INPUT_PK();
                            $input->form_pk();
                            $input->int_pk('anni', '', 'required');
                            $input->submit_pk('submit', 'invia');
                            $input->end_form_pk();


                            ?>
                        </div>
                    </div>


                </div>

                <div class="col-4" style="text-align: left ; padding-right: 3rem ">

                </div>
            </div>
        </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>

</html>