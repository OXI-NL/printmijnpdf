<?php

return [

    /*
    | Python voor de boekje-impositie (scripts/impose_booklet.py). Wijs dit naar
    | een interpreter waarin scripts/requirements.txt is geïnstalleerd, bv. een
    | virtualenv: PYTHON_BINARY=/pad/naar/project/.venv/bin/python
    */
    'python' => [
        'binary' => env('PYTHON_BINARY', 'python3'),
    ],

];
