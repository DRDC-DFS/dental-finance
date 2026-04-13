Schema::table('payment_methods', function (Blueprint $table) {
    $table->string('name')->after('id');
});