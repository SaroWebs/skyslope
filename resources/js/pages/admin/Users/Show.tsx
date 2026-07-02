import AdminLayout from "@/layouts/AdminLayout";
import { AdminUser } from "@/types";
import { Head } from "@inertiajs/react";
import { Stack } from "@mantine/core";

interface UserIndexProps{
    title?: string;
    user?: AdminUser;
    target_user: AdminUser;
}

const ShowUser=({title, user, target_user}:UserIndexProps)=>{
    return(
        <AdminLayout title={title}>
            <Head title="Staff Management" />
            <Stack gap="xl">
                {target_user?.name}
            </Stack>
        </AdminLayout>
    );
}

export default ShowUser;