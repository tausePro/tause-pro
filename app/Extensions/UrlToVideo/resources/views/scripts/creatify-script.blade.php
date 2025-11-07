{{-- createVideoData > initialize > videoConfigData . --}}

@push('script')
    <script>
        document.addEventListener('alpine:init', () => {
            // create video data
            Alpine.data('createVideoData', () => ({
                // steps to create video using creatify
                steps: ['{{ __('Product') }}', '{{ __('Detail') }}', '{{ __('Script') }}',
                    '{{ __('Composition') }}', '{{ __('Render') }}'
                ],
                // currentStep
                currentStep: 1,
                // step percent
                stepPercent: 20,
                // video config data
                videoConfigData: {},
                init() {
                    Alpine.store('createVideoData', this);
                },
                // initialize the variables
                initialize() {
                    this.currentStep = 1;
                    this.videoConfigData = {
                        language: "en",
                        no_background_music: false,
                        no_caption: false
                    };
                },
                changeStep(step) {
                    const previousStep = this.currentStep;

                    if (typeof step === 'string') {
                        if (step === 'next') {
                            this.currentStep = Math.min(5, this.currentStep + 1);
                        } else if (step === 'prev') {
                            this.currentStep = Math.max(1, this.currentStep - 1);
                        }
                    } else if (typeof step == 'number') {
                        if (this.currentStep > step) {
                            this.currentStep = Math.max(1, step);
                        } else {
                            return;
                        }
                    }

                    if (this.currentStep == 3 && this.currentStep > previousStep) {
                        Alpine.store('videoScriptData').generateAIScript();
                    }

                    if (this.currentStep == 5) {
                        Alpine.store('previewVideoData').generatePreviewVideos();
                    }
                }
            }));

            // step 1: productInformation data
            Alpine.data('productBasicInfo', () => ({
                // when submit the data to server
                submitting: false,
                // form data
                formData: {
                    url: '',
                    video_urls: [],
                    image_urls: [],
                    title: '',
                    description: '',
                },
                // when upload the files to server
                uploading: false,
                // active tab
                activeTab: "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}",
                init() {
                    Alpine.store('productBasicInfo', this);
                },
                // initialze the window
                initialize() {
                    this.submitting = false;
                    this.uploading = false;
                    this.activeTab =
                        "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}";

                    this.formData = {
                        url: '',
                        video_urls: [],
                        image_urls: [],
                        title: '',
                        description: '',
                    };
                },
                // set active tab
                setActiveTab(tab) {
                    if (this.activeTab === tab) return;
                    this.activeTab = tab;
                },
                // next step
                async nextStep() {
                    if (this.activeTab ==
                        "{{ \App\Enums\AiInfluencer\ProductTabEnum::URL->value }}"
                    ) {
                        if (this.validateWithMsg(this.formData.url, 'Please input url')) {
                            this.submitting = true;
                            try {
                                const res = await fetch(
                                    '{{ route('creatify.generate-link-by-url') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            'url': this.formData.url
                                        })
                                    })

                                const resData = await res.json();

                                if (!res.ok || resData.status == 'error') {
                                    throw new Error(resData.message ||
                                        '{{ __('Something went wrong. Please contact support for assistance') }}'
                                    );
                                }

                                Alpine.store('createVideoData').videoConfigData.link = resData
                                    .resData.link;
                                Alpine.store('createVideoData').videoConfigData.linkId = resData
                                    .resData.id;
                                Alpine.store('createVideoData').changeStep('next');
                                this.submitting = false;
                            } catch (error) {
                                toastr.error(error?.message || error);
                                this.submitting = false;
                            }
                        }
                    } else {
                        const assets = Alpine.store('assetsDetail').assets.filter(asset => asset
                            .checked);
                        if (assets.length < 1) {
                            toastr.error(
                                '{{ __('Please upload assets and select assets to use it') }}');
                            return;
                        }

                        if (!this.validateWithMsg(this.formData.title, 'Please input title') || !
                            this
                            .validateWithMsg(this.formData.description, 'Please input description')
                        ) {
                            return;
                        }

                        const videos = assets.filter(asset => asset.fileType ==
                            'video');
                        this.formData.video_urls = videos.reduce((acc, video) => {
                            acc.push(video.fileUrl);
                            return acc;
                        }, []);

                        const images = assets.filter(asset => asset.fileType ==
                            'image');
                        this.formData.image_urls = images.reduce((acc, image) => {
                            acc.push(image.fileUrl);
                            return acc;
                        }, []);

                        this.formData.url = '';

                        this.submitting = true;
                        try {
                            const res = await fetch(
                                '{{ route('creatify.generate-link-by-params') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify(this.formData)
                                })

                            const resData = await res.json();

                            if (!res.ok || resData.status == 'error') {
                                throw new Error(resData.message ||
                                    '{{ __('Something went wrong. Please contact support for assistance') }}'
                                );
                            }

                            Alpine.store('createVideoData').videoConfigData.link = resData
                                .resData.link;
                            Alpine.store('createVideoData').videoConfigData.linkId = resData.resData
                                .id;
                            Alpine.store('createVideoData').changeStep('next');
                            this.submitting = false;
                        } catch (error) {
                            toastr.error(error?.message || error);
                            this.submitting = false;
                        }
                    }
                },
                // validate if value is empty or not
                validateWithMsg(value, errorMsg) {
                    if (!value || value.trim() == '') {
                        toastr.error(errorMsg);
                        return false;
                    }
                    return true;
                },
            }));

            Alpine.data('fileUploadData', () => ({
                // accept file types
                accept: 'image/*,video/*',
                // if any files selected or not
                fileSelected: false,
                initialize() {
                    this.fileSelected = false;
                    this.$el.querySelector('input[type="file"]').files = null;
                },
                // set file selected status
                setFileSelected(status = true) {
                    this.fileSelected = status;
                },
                // upload the file
                async uploadFiles() {
                    if (!this.fileSelected) {
                        toastr.error('{{ __('Please select the files') }}')
                        return;
                    }

                    const image_files = [];
                    const video_files = [];

                    const files = this.$el.querySelector('input[type="file"]').files;

                    for (file of files) {
                        if (file.type.startsWith('image/')) {
                            image_files.push(file);
                        } else if (file.type.startsWith('video/')) {
                            video_files.push(file);
                        }
                    }

                    Alpine.store('productBasicInfo').uploading = true;

                    try {
                        const formData = new FormData;
                        image_files.forEach((file, index) => {
                            formData.append(`image_files[${index}]`, file);
                        });

                        video_files.forEach((file, index) => {
                            formData.append(`video_files[${index}]`, file);
                        });

                        const res = await fetch(
                            "{{ route('dashboard.user.ai-influencer.upload-files') }}", {
                                method: 'post',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                body: formData
                            }
                        );

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        Alpine.store('assetsDetail').addAssets(resData.resData.uploadedFiles);
                        Alpine.store('productBasicInfo').uploading = false;
                        this.$el.querySelector('input[type="file"]').files = null;
                        this.fileSelected = false;

                    } catch (error) {
                        console.error(error);
                        Alpine.store('productBasicInfo').uploading = false;
                    }
                }
            }))

            Alpine.data('assetsDetail', () => ({
                // if all assets are selcted or not
                isSelectedAll: false,
                // video and image assets
                assets: [],
                init() {
                    Alpine.store('assetsDetail', this);
                },
                initialize() {
                    this.assets = [];
                    this.isSelectedAll = false;
                },
                // add assets
                addAssets(files) {
                    let id = this.assets.length;
                    files.video_urls.forEach(video_url => {
                        this.assets.push({
                            id: id,
                            fileType: 'video',
                            fileUrl: video_url,
                            checked: true
                        })
                        id++;
                    });

                    files.image_urls.forEach(image_url => {
                        this.assets.push({
                            id: id,
                            fileType: 'image',
                            fileUrl: image_url,
                            checked: true
                        })
                        id++;
                    });

                    this.isSelectedAll = this.assets.every(asset => asset.checked);
                },
                // toggle all select
                toggleSelect() {
                    this.isSelectedAll = !this.isSelectedAll;
                    this.assets.forEach(asset => asset.checked = this.isSelectedAll);
                },
                /**select the asset */
                selectAsset(id) {
                    const asset = this.assets.find(asset => asset.id == id);
                    asset.checked = !asset.checked;
                    this.isSelectedAll = this.assets.every(asset => asset.checked);
                },
                /**Display the video duration to human like*/
                timeConvert(time) {
                    time = time ?? 0;

                    const minutes = Math.floor(time / 60);
                    const seconds = Math.floor(time % 60);
                    return (minutes > 10 ? minutes : '0' + minutes) + ':' + (seconds > 10 ? seconds :
                        '0' + seconds);
                },
            }))

            // step 3: video script data
            Alpine.data('videoScriptData', () => ({
                // fetching data or not
                fetching: false,
                // active tab
                activeTab: '',
                // generated scripts by id
                generatedScripts: [],
                // selected script id
                selectedScriptId: 0,
                // custom script
                customScriptContent: '',
                init() {
                    Alpine.store('videoScriptData', this);
                },
                initialize() {
                    this.fetching = false;
                    this.activeTab =
                        "{{ \App\Enums\AiInfluencer\ScriptTabEnum::AUTO_GENERATED_SCRIPT->value }}";
                    this.generatedScripts = [];
                    this.selectedScriptId = 0;
                    this.customScriptContent = '';
                },
                // set active tab
                setActiveTab(tab) {
                    if (this.activeTab == tab) return;
                    this.activeTab = tab;
                },
                // submit generate script task
                async generateAIScript() {
                    this.setFetchingStatus(true);
                    try {
                        const res = await fetch(
                            '{{ route('creatify.generate-script') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    'title': Alpine.store('createVideoData')
                                        .videoConfigData
                                        .link.title,
                                    'description': Alpine.store('createVideoData')
                                        .videoConfigData.link.description,
                                    'language': Alpine.store('createVideoData')
                                        .videoConfigData.language,
                                    'video_length': Alpine.store('createVideoData')
                                        .videoConfigData.video_length
                                })
                            })

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.fetchAIScript(resData.resData.id);
                    } catch (error) {
                        toastr.error(error?.message || error);
                        this.setFetchingStatus(false);
                    }
                },
                // fetch ai script
                async fetchAIScript(id) {
                    const ms = (time) => new Promise((resolve) => setTimeout(resolve, time));

                    while (true) {
                        try {
                            const res = await fetch(
                                '{{ route('creatify.get-scripts') }}' + `?ids=${id}`, {
                                    method: 'get',
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                })

                            const resData = await res.json();

                            if (!res.ok || resData.status == 'error') {
                                throw new Error(resData.message ||
                                    '{{ __('Something went wrong. Please contact support for assistance') }}'
                                );
                            }

                            const generatedScript = resData.resData[0];
                            if (generatedScript.status == 'done') {
                                this.generatedScripts = generatedScript.generated_scripts;
                                this.setFetchingStatus(false);
                                break;
                            } else if (generatedScript.status == 'pending' || generatedScript
                                .status ==
                                'in_queue' || generatedScript.status == 'running') {
                                await ms(2000);
                            } else {
                                throw new Error(
                                    'Unexpected issue happen while generate the ai script');
                            }
                        } catch (error) {
                            toastr.error(error?.message || error);
                            this.setFetchingStatus(false);
                            break;
                        }
                    }
                },
                // set fetching status
                setFetchingStatus(status = true) {
                    this.fetching = status;
                    Alpine.store('appLoadingIndicator')[status ? 'show' : 'hide']();
                },
                // select the script
                selectScript(id) {
                    if (this.selectedScriptId == id) return;
                    this.selectedScriptId = id;
                },
                // next step
                nextStep() {
                    if (this.activeTab ==
                        "{{ \App\Enums\AiInfluencer\ScriptTabEnum::AUTO_GENERATED_SCRIPT->value }}"
                    ) {
                        Alpine.store('createVideoData').videoConfigData.override_script = this
                            .generatedScripts[this.selectedScriptId].paragraphs;
                    } else {
                        if (this.customScriptContent.trim() == '') {
                            toastr.error('{{ __('Please input script content') }}');
                            return;
                        }

                        Alpine.store('createVideoData').videoConfigData.override_script = this
                            .customScriptContent.trim();
                    }

                    Alpine.store('createVideoData').changeStep('next');
                }
            }));

            // setp 4: video composition data
            Alpine.data('videoCompositionData', () => ({
                // fetching status
                fetching: false,
                // active tabl
                activeTab: '',
                // resources like avatars, musics, voices
                resources: {
                    avatars: [],
                    voices: [],
                    musics: [],
                },
                // selected resources
                selectedResources: {
                    avatarId: '',
                    voiceId: '',
                    musicId: '',
                    captionId: '',
                },
                // search key for resources
                searchKey: '',
                initialize() {
                    this.fetching = false;
                    this.activeTab =
                        "{{ \App\Enums\AiInfluencer\CompositionEditTabEnum::AVATAR->value }}";

                    if (this.resources.avatars.length == 0) {
                        this.fetchAvatars();
                    }
                    if (this.resources.voices.length == 0) {
                        this.fetchVoices();
                    }
                    if (this.resources.musics.length == 0) {
                        this.fetchMusics();
                    }

                    this.selectedResources = {
                        avatarId: '',
                        voiceId: '',
                        musicId: '',
                        captionId: '',
                    };
                },
                // fetch avatars
                async fetchAvatars() {
                    try {
                        const res = await fetch(
                            '{{ route('creatify.get-avatars') }}', {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching avatars");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.resources.avatars = resData.resData;
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the avatar
                selectAvatar(id) {
                    if (this.selectedResources.avatarId == id) return;
                    this.selectedResources.avatarId = id;
                },
                // fetch voices
                async fetchVoices() {
                    try {
                        const res = await fetch(
                            '{{ route('creatify.get-voices') }}', {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching voices");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.resources.voices = resData.resData;
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the voice
                selectVoice(id) {
                    if (this.selectedResources.voiceId == id) return;
                    this.selectedResources.voiceId = id;
                },
                // fetch voices
                async fetchMusics(pageNo = 1) {
                    try {
                        const res = await fetch(
                            '{{ route('creatify.get-musics') }}' +
                            `?page_size=100&page=${pageNo}`, {
                                headers: {
                                    'Accept': 'application/json',
                                }
                            }
                        )

                        if (!res.ok) {
                            throw new Error("Error happen while fetching musics");
                        }

                        const resData = await res.json();

                        if (resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.resources.musics.push(...resData.resData.results);

                        if (this.resources.musics.length < resData
                            .resData.count) {
                            this.fetchMusics(pageNo + 1);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },
                // select the music
                selectMusic(id) {
                    if (this.selectedResources.musicId == id) return;
                    this.selectedResources.musicId = id;
                },
                // set active tab
                setActiveTab(tab) {
                    if (this.activeTab == 'tab') return;
                    this.activeTab = tab;
                },
                // next step => preview videos
                nextStep() {
                    if (this.selectedResources.avatarId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.override_avatar = this
                            .selectedResources.avatarId;
                    }
                    if (this.selectedResources.voiceId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.override_voice = this
                            .selectedResources.voiceId;
                    }
                    if (this.selectedResources.captionId.trim() != '') {
                        Alpine.store('createVideoData').videoConfigData.caption_style = this
                            .selectedResources.captionId;
                    }

                    if (this.selectedResources.musicId.trim() != '') {
                        const music = this.resources.musics.find(music => music.id == this
                            .selectedResources.musicId);
                        Alpine.store('createVideoData').videoConfigData.background_music_url = music
                            .url;
                    }

                    Alpine.store('createVideoData').changeStep('next');
                },
            }));

            // composition voice tab
            Alpine.data('compositionVoiceData', () => ({
                // playing voice
                playingVoice: null,
                initialize() {
                    this.playingVoice = null;
                },
                // play or pause the voice
                playVoice(voice) {
                    if (!this.playingVoice) {
                        this.playingVoice = voice;
                    } else if (this.playingVoice.accents[0].id != voice.accents[0].id) {
                        if (this.playingVoice.playing) {
                            this._changeVoicePlayStatus(this.playingVoice);
                        }
                        this.playingVoice = voice;
                    }

                    this._changeVoicePlayStatus(voice);
                },
                // when click outside of area, it will pause the playing voice
                pausePlayingVoice() {
                    if (this.playingVoice) {
                        if (this.playingVoice && this.playingVoice.playing) {
                            this._changeVoicePlayStatus(this.playingVoice);
                        }
                        this.playingVoice = null;
                    }
                },
                // change voice play status
                _changeVoicePlayStatus(voice) {
                    const audioElement = document.getElementById('composition-voice-' + voice.accents[0]
                        .id);
                    voice.playing = !voice.playing;

                    if (voice.playing) {
                        audioElement.play();
                    } else {
                        audioElement.pause();
                        audioElement.currentTime = 0;
                    }
                }
            }));

            // composition music tab
            Alpine.data('compositionMusicData', () => ({
                // playing music
                playingMusic: null,
                initialize() {
                    this.playingMusic = null;
                },
                // play or pause the music
                playMusic(music) {
                    if (!this.playingMusic) {
                        this.playingMusic = music;
                    } else if (this.playingMusic.id != music.id) {
                        if (this.playingMusic.playing) {
                            this._changeMusicPlayStatus(this.playingMusic);
                        }
                        this.playingMusic = music;
                    }

                    this._changeMusicPlayStatus(music);
                },
                // when click outside of area, it will pause the playing music
                pausePlayingMusic() {
                    if (this.playingMusic) {
                        if (this.playingMusic && this.playingMusic.playing) {
                            this._changeMusicPlayStatus(this.playingMusic);
                        }
                        this.playingMusic = null;
                    }
                },
                // convert duration to human like
                getDurationForHuman(duration) {
                    const mins = Math.floor(duration / 60);
                    const secs = Math.floor(duration % 60);

                    return (mins < 10 ? `0${mins}` : mins) + ':' + (secs < 10 ? `0${secs}` : secs)
                },
                // change voice play status
                _changeMusicPlayStatus(music) {
                    const musicElement = document.getElementById('composition-music-' + music.id);
                    music.playing = !music.playing;

                    if (music.playing) {
                        musicElement.play();
                    } else {
                        musicElement.pause();
                        musicElement.currentTime = 0;
                    }
                }
            }));

            // setp 5: preview video
            Alpine.data('previewVideoData', () => ({
                // fetching status
                fetching: false,
                // selected preview media job
                media_job: '',
                // generate preview video task id
                taskId: '',
                // preview videos
                previews: [],
                init() {
                    Alpine.store('previewVideoData', this);
                },
                // initialize
                initialize() {
                    this.fetching = false;
                    this.previews = [];
                    this.media_job = '';
                    this.taskId = '';
                },
                // submit generate preview video
                async generatePreviewVideos() {
                    this.setFetchingStatus(true);
                    try {
                        const cloneConfigData = JSON.parse(JSON.stringify(Alpine.store(
                                'createVideoData')
                            .videoConfigData));
                        cloneConfigData.name = cloneConfigData.link.title || 'Video';
                        cloneConfigData.link = cloneConfigData.linkId;

                        const res = await fetch(
                            '{{ route('creatify.generate-preview-videos') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(cloneConfigData)
                            })

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        this.taskId = resData.resData.id;
                        this.getPreviewVideoResult(resData.resData.id);
                    } catch (error) {
                        toastr.error(error?.message || error);
                        this.setFetchingStatus(false);
                    }
                },
                // fetch preview video result
                async getPreviewVideoResult(id) {
                    const ms = (time) => new Promise((resolve) => setTimeout(resolve, time));

                    while (true) {
                        try {
                            const res = await fetch(
                                '{{ route('creatify.get-video-result') }}' + `?id=${id}`, {
                                    method: 'get',
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                })

                            const resData = await res.json();

                            if (!res.ok || resData.status == 'error') {
                                throw new Error(resData.message ||
                                    '{{ __('Something went wrong. Please contact support for assistance') }}'
                                );
                            }

                            const previewResult = resData.resData;
                            if (previewResult.previews.length > 0) {
                                this.previews = previewResult.previews;
                                this.setFetchingStatus(false);
                                break;
                            } else if (previewResult.status == 'pending' || previewResult
                                .status ==
                                'in_queue' || previewResult.status == 'running') {
                                await ms(2000);
                            } else {
                                throw new Error(
                                    'Unexpected issue happen while generate the preview videos');
                            }
                        } catch (error) {
                            toastr.error(error?.message || error);
                            this.setFetchingStatus(false);
                            break;
                        }
                    }
                },
                // render final video
                async renderVideo() {
                    if (this.media_job == '') {
                        toastr.error(
                            '{{ __('Please select preview video to render final video') }}');
                        return;
                    }

                    this.setFetchingStatus(true);

                    try {
                        const res = await fetch("{{ route('creatify.render-final-video') }}", {
                            method: 'post',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                id: this.taskId,
                                media_job: this.media_job
                            })
                        });

                        const resData = await res.json();

                        if (!res.ok || resData.status == 'error') {
                            throw new Error(resData.message ||
                                '{{ __('Something went wrong. Please contact support for assistance') }}'
                            );
                        }

                        const video = {
                            'task_id': resData.resData.id,
                            'status': 'in_progress',
                            'used_ai_tool': 'creatify'
                        };

                        window.location = "{{ route('dashboard.user.ai-influencer.index') }}";
                        // Alpine.store('exportedVideoData').addNewInProgressVideo(
                        //     video);
                        // Alpine.store('aiUrlToVideoData').toggleUrlToVideoWindow(
                        //     false);

                        // $('iframe').attr('src', $('iframe').attr('src'));

                        // this.setFetchingStatus(false);
                    } catch (error) {
                        console.error(error);
                        this.setFetchingStatus(false);
                    }
                },
                // set fetching status
                setFetchingStatus(status = true) {
                    this.fetching = status;
                    Alpine.store('appLoadingIndicator')[status ? 'show' : 'hide']();
                },
                // select preview video
                selectPreview(media_job) {
                    if (this.media_job == media_job) return;
                    this.media_job = media_job;
                },
            }));
        });
    </script>
@endpush
