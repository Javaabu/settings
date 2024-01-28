---
title: Model Binding and Default Values
---

You can use the `default` attribute to specify the default value of the element.

```html
<x-form-textarea name="motivation" default="I want to use this package because..." />
```

# Binding a target

Instead of setting a default value, you can also pass in a target, like an Eloquent model. Now the component will get the value from the target by the `name`.

```html
<x-form-textarea name="description" :model="$video" />
```

In the example above, where `$video` is an Eloquent model, the default value will be `$video->description`.

# Binding a form

Instead of binding individual elements, you can bind a whole form.

```html
<x-form :model="$video">
    <x-form-input name="title" />
    <x-form-textarea name="description" />
</x-form>
```

In the example above, the elements inside the form will be bound to the `$video` Eloquent model.
The default value of the `title` text input will be `$video->title` and the default value of the `description` textarea will be `$video->description`.

# Binding a target to multiple elements

You can also bind a target by using the `@model` directive. This will bind the target to all elements until the `@endmodel` directive.


```html
<div>
    @model($video)
        <x-form-input name="title" />
        <x-form-textarea name="description" />
    @endmodel
</div>
```

You can even mix targets!

```html
<x-form>
    @model($user)
        <x-form-input name="full_name" label="Full name" />

        @model($userProfile)
            <x-form-textarea name="biography" label="Biography" />
        @endmodel

        <x-form-input name="email" label="Email address" />
    @endmodel
</x-form>
```

# Override or remove a binding

You can override the `@model` directive by passing a target directly to the element using the `:model` attribute.
If you want to remove a binding for a specific element, pass in false.

```html
<x-form>
    @model($video)
        <x-form-input name="title" label="Title" />
        <x-form-input :model="$videoDetails" name="subtitle" label="Subtitle" />
        <x-form-textarea :model="false" name="description" label="Description" />
    @endmodel
</x-form>
```
