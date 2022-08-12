<?php


namespace App\Services;


use App\Models\ConsumableRecord;
use DB;

class ConsumableService
{
    /**
     * 配件删除.
     *
     * @param $consumable_id
     */
    public static function consumableDelete($consumable_id)
    {
        $consumable = ConsumableRecord::where('id', $consumable_id)->first();
        if (!empty($consumable)) {
            $sql = " select TABLE_NAME from information_schema.columns where TABLE_SCHEMA = '".$_ENV['DB_DATABASE']."' and column_name='consumable_id'";
            $tables = DB::select($sql);
            $tables = json_decode(json_encode($tables),true);
            foreach ($tables as $table)
            {
                $sql2 = "delete from ".$table['TABLE_NAME']." where consumable_id = ".$consumable_id;
                DB::delete($sql2);
            }
            $consumable->delete();
        }
    }

    /**
     * 删除配件（强制）.
     *
     * @param $consumable_id
     */
    public static function consumableForceDelete($consumable_id)
    {
        $consumable = ConsumableRecord::where('id', $consumable_id)
            ->withTrashed()
            ->first();
        if (!empty($consumable)) {
            $sql = " select TABLE_NAME from information_schema.columns where TABLE_SCHEMA = '".$_ENV['DB_DATABASE']."' and column_name='consumable_id'";
            $tables = DB::select($sql);
            $tables = json_decode(json_encode($tables),true);
            foreach ($tables as $table)
            {
                $sql2 = "delete from ".$table['TABLE_NAME']." where consumable_id = ".$consumable_id;
                DB::delete($sql2);
            }
            $consumable->forceDelete();
        }
    }
}
