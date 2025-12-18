<?php

namespace App\Kis;

use App\Models\User;
use App\Models\MasterCode;
use App\Models\GenerateNumber;
use Illuminate\Support\Facades\DB;

class GeneratingNumber
{
    /**
     * find Number Available */
    public static function findNewNumber($type, $objCode)
    {
        $findMC = MasterCode::where(['type' => $type, 'state' => true])->first();
        if(!$findMC)
        {
            return null;
        }
        $firstCode = $findMC->first_code;
        $secondCode = self::secondCode($findMC->need_initial, $findMC->initial_by, $objCode);
        $thirdCode = self::thirdCode($findMC->month_type);
        $fourthCode = date('y');
        $countNumber = self::runningNumber($findMC->start_by,$findMC->reset_type, $findMC->type, $findMC->initial_by, $objCode);
        $runningNumber = sprintf('%03s', $countNumber);

        $result = $firstCode."".$secondCode."".$findMC->seperate_by."".$thirdCode."".$findMC->seperate_by."".$fourthCode."".$findMC->seperate_by."".$runningNumber;
        return [
            'order' => $countNumber,
            'number' => $result,
            'initial' => $findMC->need_initial ? ($findMC->initial_by == 'subject' ? request()->auth->code : $objCode) : null
        ];
        // return $result;
    }

    private static function secondCode($needInitial, $typeInitial, $objCode)
    {
        if ($needInitial) {
            if ($typeInitial == 'subject') {
                return "-" . request()->auth->code;
            } elseif ($typeInitial == 'object') {
                return "-" . $objCode;
            } else {
                return null;
            }
        }
        return null;
    }

    private static function thirdCode($type)
    {
        if ($type == 'roman') {
            return self::getRomawi(date('n'));
        }
        return date('m');
    }

    private static function runningNumber($startType, $resetType, $numberType, $initialBy = null, $objCode = null)
    {
        if($startType == 'general' && $resetType == 'monthly')
        {
            return self::generalMonthly($numberType) == null ? 1 : self::generalMonthly($numberType);
        }
        if($startType == 'general' && $resetType == 'annually')
        {
            return self::generalAnnually($numberType) == null ? 1 : self::generalAnnually($numberType);
        }
        if($startType == 'spesific' && $resetType == 'monthly')
        {
            return self::spesificMonthly($initialBy, $numberType, $objCode) == null ? 1 : self::spesificMonthly($initialBy, $numberType, $objCode);
        }
        if($startType == 'spesific' && $resetType == 'annually')
        {
            return self::spesificAnnually($initialBy, $numberType, $objCode) == null ? 1 : self::spesificAnnually($initialBy, $numberType, $objCode);
        }
    }

    private static function spesificMonthly($initialBy, $numberType, $objCode = null)
    {
        $number = 0;
        $myAvailable = GenerateNumber::where(['create_by' => request()->auth->id, 'available' => true])->where(DB::raw("MONTH(created_at)"), date('m'))->where('type', $numberType)->orderBy('order', 'desc')->first();
        if ($myAvailable) {
            return $myAvailable->order;
        }
        if($initialBy == 'subject')
        {
            $findCount = GenerateNumber::where(DB::raw("MONTH(created_at)"), date('m'))->where('type', $numberType)->where('initial', request()->auth->code)->orderBy('order', 'desc')->first();
        } else {
            $findCount = GenerateNumber::where(DB::raw("MONTH(created_at)"), date('m'))->where('type', $numberType)->where('initial', $objCode)->orderBy('order', 'desc')->first();
        }
        if (!$findCount) {
            $getCount = $number + 1;
        } else {
            if ($findCount->available) {
                if (strtotime($findCount->expire_at) > strtotime(date('Y-m-d H:i:s'))) {
                    if ($findCount->create_by == request()->auth->id) {
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order + 1;
                    }
                } else {
                    if ($findCount->create_by == request()->auth->id) {
                        $findCount->expire_at = date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime(date('Y-m-d H:i:s'))));
                        $findCount->update_by = request()->auth->id;
                        $findCount->save();
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order;
                    }
                }
            } else {
                $getCount = $findCount->order + 1;
            }
        }
        return $getCount;
    }
    
    private static function spesificAnnually($initialBy, $numberType, $objCode = null)
    {
        $number = 0;
        if($initialBy == 'subject')
        {
            $myAvailable = GenerateNumber::where(['create_by' => request()->auth->id, 'available' => true])->where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->where('initial', request()->auth->code)->orderBy('order', 'desc')->first();
            if ($myAvailable) {
                return $myAvailable->order;
            }
            $findCount = GenerateNumber::where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->where('initial', request()->auth->code)->orderBy('order', 'desc')->first();
        } else {
            $myAvailable = GenerateNumber::where(['create_by' => request()->auth->id, 'available' => true])->where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->where('initial', $objCode)->orderBy('order', 'asc')->first();
            if ($myAvailable) {
                return $myAvailable->order;
            }
            $findCount = GenerateNumber::where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->where('initial', $objCode)->orderBy('order', 'desc')->first();
        }
        if (!$findCount) {
            $getCount = $number + 1;
        } else {
            if ($findCount->available) {
                if (strtotime($findCount->expire_at) > strtotime(date('Y-m-d H:i:s'))) {
                    if ($findCount->create_by == request()->auth->id) {
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order + 1;
                    }
                } else {
                    if ($findCount->create_by == request()->auth->id) {
                        $findCount->expire_at = date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime(date('Y-m-d H:i:s'))));
                        $findCount->update_by = request()->auth->id;
                        $findCount->save();
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order;
                    }
                }
            } else {
                $getCount = $findCount->order + 1;
            }
        }
        return $getCount;
    }

    private static function generalMonthly($numberType)
    {
        $number = 0;
        $myAvailable = GenerateNumber::where(['create_by' => request()->auth->id, 'available' => true])->where(DB::raw("MONTH(created_at)"), date('m'))->where('type', $numberType)->orderBy('order', 'desc')->first();
        if ($myAvailable) {
            return $myAvailable->order;
        }
        $findCount = GenerateNumber::where(DB::raw("MONTH(created_at)"), date('m'))->where('type', $numberType)->orderBy('order', 'desc')->first();
        if (!$findCount) {
            $getCount = $number + 1;
        } else {
            if ($findCount->available) {
                if (strtotime($findCount->expire_at) > strtotime(date('Y-m-d H:i:s'))) {
                    if ($findCount->create_by == request()->auth->id) {
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order + 1;
                    }
                } else {
                    if ($findCount->create_by == request()->auth->id) {
                        $findCount->expire_at = date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime(date('Y-m-d H:i:s'))));
                        $findCount->update_by = request()->auth->id;
                        $findCount->save();
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order;
                    }
                }
            } else {
                $getCount = $findCount->order + 1;
            }
        }
        return $getCount;
    }
    
    private static function generalAnnually($numberType)
    {
        $number = 0;
        $myAvailable = GenerateNumber::where(['create_by' => request()->auth->id, 'available' => true])->where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->orderBy('order', 'desc')->first();
        if($myAvailable)
        {
            return $myAvailable->order;
        }
        $findCount = GenerateNumber::where(DB::raw("YEAR(created_at)"), date('Y'))->where('type', $numberType)->orderBy('order', 'desc')->first();
        
        if (!$findCount) {
            $getCount = $number + 1;
        } else {
            if ($findCount->available) {
                if (strtotime($findCount->expire_at) > strtotime(date('Y-m-d H:i:s'))) {
                    if ($findCount->create_by == request()->auth->id) {
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order + 1;
                    }
                } else {
                    if ($findCount->create_by == request()->auth->id) {
                        $findCount->expire_at = date('Y-m-d H:i:s', strtotime('+ 3 minutes', strtotime(date('Y-m-d H:i:s'))));
                        $findCount->update_by = request()->auth->id;
                        $findCount->save();
                        $getCount = $findCount->order;
                    } else {
                        $getCount = $findCount->order;
                    }
                }
            } else {
                $getCount = $findCount->order + 1;
            }
        }
        return $getCount;
    }

    public static function getRomawi($month)
    {
        if ($month == 1) {
            return "I";
        }
        if ($month == 2) {
            return "II";
        }
        if ($month == 3) {
            return "III";
        }
        if ($month == 4) {
            return "IV";
        }
        if ($month == 5) {
            return "V";
        }
        if ($month == 6) {
            return "VI";
        }
        if ($month == 7) {
            return "VII";
        }
        if ($month == 8) {
            return "VIII";
        }
        if ($month == 9) {
            return "IX";
        }
        if ($month == 10) {
            return "X";
        }
        if ($month == 11) {
            return "XI";
        }
        if ($month == 12) {
            return "XII";
        }
    }
    
}
