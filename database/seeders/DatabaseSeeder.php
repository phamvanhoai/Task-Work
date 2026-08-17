<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $people = collect([
            ['Nguyễn Văn A', 'admin@taskwork.local', 'admin'],
            ['Trần Thị B', 'tran.b@taskflow.local', 'member'],
            ['Lê Văn C', 'le.c@taskflow.local', 'member'],
            ['Phạm Văn D', 'pham.d@taskflow.local', 'member'],
            ['Hoàng Thị E', 'hoang.e@taskflow.local', 'member'],
        ])->map(fn ($person) => User::updateOrCreate(
            ['email' => $person[1]],
            ['name' => $person[0], 'password' => 'ChangeMe123!', 'role' => $person[2]],
        ));

        $projectRows = [
            ['WEB', 'Website redesign', 'Thiết kế lại website công ty với trải nghiệm hiện đại.', 'active', 'high', 0, 32],
            ['APP', 'Mobile App', 'Xây dựng ứng dụng di động cho khách hàng.', 'active', 'high', 2, 46],
            ['MKT', 'Marketing Campaign', 'Chiến dịch marketing ra mắt sản phẩm.', 'active', 'medium', 1, 25],
            ['PROD', 'Product Launch', 'Chuẩn bị kế hoạch ra mắt sản phẩm mới.', 'planning', 'high', 0, 58],
            ['INT', 'Internal Tools', 'Phát triển bộ công cụ quản trị nội bộ.', 'active', 'medium', 3, 40],
        ];

        $projects = collect($projectRows)->map(function ($row) use ($people) {
            $project = Project::updateOrCreate(['key' => $row[0]], [
                'name' => $row[1], 'description' => $row[2], 'status' => $row[3], 'priority' => $row[4],
                'owner_id' => $people[$row[5]]->id, 'start_date' => today()->subDays(30), 'due_date' => today()->addDays($row[6]),
            ]);
            $project->members()->syncWithoutDetaching($people->mapWithKeys(fn ($user, $index) => [$user->id => ['role' => $index === 0 ? 'owner' : 'member']])->all());

            return $project;
        });

        $tasks = [
            ['Thiết kế UI/UX trang dashboard', 0, 0, 0, 'in_progress', 'high', 2],
            ['Nghiên cứu giao diện trang chủ', 0, 1, 0, 'todo', 'medium', 4],
            ['Phát triển API người dùng', 1, 2, 0, 'in_progress', 'high', 3],
            ['Review giao diện mobile', 1, 3, 1, 'review', 'medium', 1],
            ['Phân tích yêu cầu dự án', 3, 0, 0, 'done', 'high', -1],
            ['Kiểm tra báo cáo thống kê', 0, 1, 0, 'todo', 'low', 0],
            ['Viết content cho landing page', 2, 2, 1, 'in_progress', 'medium', 5],
            ['Thiết kế wireframe', 0, 0, 0, 'done', 'low', -2],
            ['Kiểm thử chức năng đăng nhập', 1, 3, 2, 'todo', 'medium', 4],
            ['Setup môi trường dự án', 4, 1, 0, 'done', 'high', -3],
            ['Tối ưu hiệu năng website', 0, 3, 0, 'todo', 'medium', 6],
            ['Tích hợp thanh toán trực tuyến', 1, 2, 0, 'in_progress', 'urgent', 8],
            ['Chuẩn bị nội dung ra mắt', 3, 4, 1, 'review', 'high', 7],
            ['Xây dựng trang quản trị', 4, 2, 0, 'in_progress', 'medium', 10],
            ['Kiểm thử responsive', 0, 4, 1, 'done', 'medium', -4],
        ];

        foreach ($tasks as $row) {
            Task::updateOrCreate(['project_id' => $projects[$row[1]]->id, 'title' => $row[0]], [
                'description' => 'Dữ liệu mẫu phục vụ giao diện TaskFlow.', 'assignee_id' => $people[$row[2]]->id,
                'reporter_id' => $people[$row[3]]->id, 'status' => $row[4], 'priority' => $row[5],
                'due_date' => today()->addDays($row[6]), 'completed_at' => $row[4] === 'done' ? now() : null,
            ]);
        }
    }
}
