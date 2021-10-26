<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function upload(UploadRequest $request): JsonResponse
    {
        // Hard coded to one week prior to March 9th, 2018 for test purposes.
        $cutoff = Carbon::create(2018, 2, 9)->subWeek();

        $sent_to = [];

        $data = [
            'results' => []
        ];

        // check if the file object exists
        if ($csv = $request->file('csv')) {
            // move the file to storage.
            // this will be used for parsing the invitations.
            $filename = $csv->getClientOriginalName();
            $csv->move(storage_path(), $filename);

            $filepath = storage_path($filename);

            // if the file move was successful, open a resource handle.
            if ($resource = fopen($filepath, 'r')) {
                $index = 0;

                // start reading the csv file row by row, up to 1000 characters.
                while (($row = fgetcsv($resource, 1000, ",")) !== false) {
                    // we're assuming the first row is always a header row, so lets append our output there.
                    if ($index === 0) {
                        $data['columns'] = array_merge($row, ['sent', 'via', 'details']);
                    } else {
                        $sent = false;
                        $via = 'N/A';

                        $trans_date = $row[1];
                        $trans_time = $row[2];
                        $cust_num = $row[3];
                        $phone = $row[6];
                        $email = $row[5];

                        $row_date = Carbon::parse($trans_date . ' ' . $trans_time);

                        logger($cutoff->toDateTimeString());
                        logger($row_date->toDateTimeString());

                        // all invitations must be sent within the past seven (7) days, those outside that range are omitted.
                        if ($row_date->lt($cutoff)) {
                            $details = 'transaction outside date range';
                        } else {
                            // 1) send an invitation via text message - if a phone number has been provided and a previous invitation sent.
                            // or 2) send via email - if NO phone number has been provided, an email is present and no previous invitation sent.
                            // if neither case be satisfied provide a descriptive feedback message indicating the reason.
                            if ($phone) {
                                $via = 'text';

                                if (!in_array($cust_num, $sent_to)) {
                                    $sent = true;
                                    $details = 'text invite successfully sent';
                                } else {
                                    $details = 'invite already sent to customer';
                                }
                            } else if ($email) {
                                $via = 'email';

                                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $details = 'invalid email address';
                                } else {
                                    if (!in_array($cust_num, $sent_to)) {
                                        $sent = true;
                                        $details = 'email invite successfully sent';
                                    } else {
                                        $details = 'invite already sent to customer';
                                    }
                                }
                            } else {
                                $via = 'n/a';
                                $details = 'no phone number or email address provided';
                            }
                        }

                        if ($sent) {
                            $sent_to[] = $cust_num;
                        }

                        $data['results'][] = array_merge($row, [
                            $sent,
                            $via,
                            $details
                        ]);
                    }

                    $index++;
                }

                fclose($resource);
            }
        }

        return response()->json($data);
    }
}
