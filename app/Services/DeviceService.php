<?php

namespace App\Services;

use App\Models\DeviceRecord;
use App\Models\DeviceTrack;
use App\Models\PartTrack;
use App\Models\SoftwareTrack;
use App\Support\Support;
use DB;

/**
 * 和设备记录相关的功能服务
 * Class DeviceService.
 */
class DeviceService
{
    /**
     * 获取设备的履历清单.
     *
     * @param $id
     *
     * @return array
     */
    public static function history($id): array
    {
        $data = [];

        $single = [
            'type' => '',
            'name' => '',
            'status' => '',
            'style' => '',
            'datetime' => '',
        ];

        // 处理设备使用者变动履历
        $device_tracks = DeviceTrack::withTrashed()
            ->where('device_id', $id)
            ->get();
        foreach ($device_tracks as $device_track) {
            $single['type'] = '用户';
            $user = $device_track->user()->withTrashed()->first();
            $username = $user->name;
            $department = $device_track->user()
                ->withTrashed()
                ->first()
                ->department()
                ->withTrashed()
                ->first();
            if (empty($department)) {
                $department = '无部门';
            } else {
                $department = $department->name;
            }
            $single['name'] = $username . ' - ' . $department;
            $data = Support::itemTrack($single, $device_track, $data);
        }

        // 处理设备配件变动履历
        $part_tracks = PartTrack::withTrashed()
            ->where('device_id', $id)
            ->get();
        foreach ($part_tracks as $part_track) {
            $single['type'] = trans('main.part');
            $part = $part_track->part()->withTrashed()->first();
            $single['name'] = $part->asset_number . ' - ' . $part->specification;
            $data = Support::itemTrack($single, $part_track, $data);
        }

        // 处理设备软件变动履历
        $software_tracks = SoftwareTrack::withTrashed()
            ->where('device_id', $id)
            ->get();
        foreach ($software_tracks as $software_track) {
            $single['type'] = trans('main.software');
            $software = $software_track->software()->withTrashed()->first();
            $single['name'] = $software->name . ' ' . $software->version;
            $data = Support::itemTrack($single, $software_track, $data);
        }

        $datetime = array_column($data, 'datetime');
        array_multisort($datetime, SORT_DESC, $data);

        return $data;
    }

    /**
     * 报废设备.
     *
     * @param $device_id
     */
    public static function deviceDiscard($device_id): void
    {
        $device_record = DeviceRecord::where('id', $device_id)->first();
        if (!empty($device_record)) {
            $device_record->discard();
        }
    }

    /**
     * 撤销报废设备.
     *
     * @param $device_id
     */
    public static function deviceReDiscard($device_id): void
    {
        $device_record = DeviceRecord::where('id', $device_id)->first();
        if (!empty($device_record)) {
            $device_record->cancelDiscard();
        }
    }

    /**
     * 删除设备.
     *
     * @param $device_id
     */
    public static function deviceDelete($device_id): void
    {
        $device_record = DeviceRecord::where('id', $device_id)->first();
        if (!empty($device_record)) {
            $sql = " select TABLE_NAME from information_schema.columns where TABLE_SCHEMA = '".$_ENV['DB_DATABASE']."' and column_name='device_id'";
            $tables = DB::select($sql);
            $tables = json_decode(json_encode($tables),true);
            foreach ($tables as $table)
            {
                $sql2 = "delete from ".$table['TABLE_NAME']." where device_id = ".$device_id;
                DB::delete($sql2);
            }
            $device_record->delete();
        }
    }

    /**
     * 恢复删除的设备.
     *
     * @param $device_id
     */
    public static function deviceReDelete($device_id): void
    {
        $device_record = DeviceRecord::where('id', $device_id);
        if (!empty($device_record)) {
            $device_record->restore();
        }
    }

    /**
     * 删除设备（强制）.
     *
     * @param $device_id
     */
    public static function deviceForceDelete($device_id): void
    {
        $device_record = DeviceRecord::where('id', $device_id)
            ->withTrashed()
            ->first();
        if (!empty($device_record)) {
            $sql = " select TABLE_NAME from information_schema.columns where TABLE_SCHEMA = '".$_ENV['DB_DATABASE']."' and column_name='device_id'";
            $tables = DB::select($sql);
            $tables = json_decode(json_encode($tables),true);
            foreach ($tables as $table)
            {
                $sql2 = "delete from ".$table['TABLE_NAME']." where device_id = ".$device_id;
                DB::delete($sql2);
            }
            $device_record->forceDelete();
        }
    }
}
