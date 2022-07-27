<template>
    <div class="bg-white w-full space-y-5 px-5 py-10 rounded-md">
        <p>To join an existing team, click the "accept invitation" button in the invitation mail.</p>

        <p>Didn't get the invitation mail? Ask a member of the organization to re-send the invitation.</p>
        <div class="w-full text-right">
            <button
                @click="$emit('change')"
                class="text-primary/80 hover:text-primary font-bold underline">
                Create my own Organization
            </button>
        </div>
    </div>
</template>
