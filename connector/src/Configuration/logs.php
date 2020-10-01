<?php

/**
 * @author Inbenta <https://www.inbenta.com/>
 */

return [

    // <0 | 1 | 2> Disabled / Errors / Verbose (all records)
    'log_level' => 2,

    // Print the logs with echo
    'echo_logs' => false,

    // Send the logs to the error_log function (log to Heroku)
    'error_log' => true,

    // Template of text logs. Available placeholders: {{req_id}}, {{date}} and {{data}}
    'text_log_template' => "Inbenta[{{req_id}}]:\t{{data}}\n",

    // Template of the exception logs. Available placeholders: {{req_id}}, {{date}}, {{message}}, {{code}}, {{file}}, {{line}}, {{trace}}
    'exception_log_template' => "Inbenta[{{req_id}}]:\tException '{{message}}' on {{file}}:{{line}}\nTrace: {{trace}}\n",
];
