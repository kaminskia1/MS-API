<?php

// Integrity mini-api

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('CFG_LOADED')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Status
 *
 * @response 500 INTERNAL_ERROR
 * @response 200 SUCCESS
 */
class Status implements Endpoint
{

    /**
     * Initialize a dynamic version of the class from a static call
     *
     * @return Status
     */
    public static function i(): Status
    {
        return new Status();
    }

    /**
     * Mini-api main function
     */
    public function run(): void
    {
        // Compile status and set it to $data
        if ($data = $this->compileStatus()) {

            // Output the status
            API::i([$data], 200)->output();

        } else {

            // 500 Error has occurred :(
            API::i([
                'error' => "INTERNAL_ERROR"
            ], 500)->output();

        }

    }

    /**
     * Compile the retreived status data
     *
     * @return array
     */
    public function compileStatus(): array
    {
        // Grab status data from DB
        $statusData = $this->grabStatusData();

        // Cycle through all of the objects, if they exist
        $output = [];
        if (count($statusData) > 0) {
            foreach ($statusData as $cheat) {

                // Decode the data and push it to output
                $cheat->p_cbpanel_data = json_decode($cheat->p_cbpanel_data);
                if ($cheat->p_cbpanel_data->isCheat) {
                    $newCheat = (object)[
                        'ID' => $cheat->p_id,
                        'Name' => $cheat->p_name,
                        'Message' => $cheat->p_cbpanel_data->statusMessage,
                        'Value' => $cheat->p_cbpanel_data->statusCode,
                        'Slots' => (object)[
                            'Used' => $cheat->p_cbpanel_data->slots->used,
                            'Max' => $cheat->p_cbpanel_data->slots->max,
                        ]
                    ];

                    array_push($output, $newCheat);
                }
            }
        }
        return $output;
    }

    /**
     * Grab data required to generate a status
     *
     * @return array
     */
    protected function grabStatusData(): array
    {
        return Db::i()->select('p_id, p_name, p_cbpanel_data', 'nexus_packages p_id')->table();
    }


}